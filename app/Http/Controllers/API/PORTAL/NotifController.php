<?php

namespace App\Http\Controllers\API\PORTAL;

use Illuminate\Http\Request;
use App\Models\PORTAL\PortalNotif;
use App\Http\Controllers\API\PORTAL\BaseController as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Models\CMS\FormAnswerUserDet;
use App\Models\CMS\FormShareDet;
use App\Traits\TOS\TrainingTraits;
use App\Traits\CMS\FormsTraits;

class NotifController extends BaseController
{
    use TrainingTraits, FormsTraits;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $data = PortalNotif::where('pnm_to_users', $request->header('username'))
            ->where(DB::raw("(
                CASE WHEN pnm_end_date IS NULL OR pnm_end_date = '1900-01-01 00:00:00'
                    THEN 1
                    -- ELSE CASE WHEN GETDATE() <= pnm_end_date
                    ELSE CASE WHEN GETDATE() >= pnm_start_date AND GETDATE() <= pnm_end_date
                        THEN 1
                        ELSE 0
                    END
                END
            )"), 1)
            ->with('shared.forms.formMaster')
            ->orderBy('created_at', 'desc')
            ->where('pnm_notif_loc', 'portal')
            ->get()
            ->toArray();

        $hasil = [];
        foreach ($data as $key => $value) {
            // $hasil[] = $value->shared->forms->id;
            if (isset($value['shared'])) {
                if ($value['shared']['forms']['cfmt_quiz_flag'] == 1) {
                    $cekJawaban = FormAnswerUserDet::where('p_u_username', $request->header('username'))->where('cfm_id', $value['shared']['forms']['id'])->get()->toArray();
                    $cekListHasil = $this->getTrainingList($request->header('username'), $value['shared']['forms']['id'])[0];

                    $hasil[] = array_merge($value, ['answers' => $cekJawaban, 'listHasil' => $cekListHasil]);
                } else {
                    $hasil[] = $value;
                }
            }
        }

        return $this->handleResponse($hasil, 'Data Found !');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validate = $request->validate([
            'pnm_from_users' => 'required|string',
            'pnm_to_users' => 'required|string',
            'pnm_title' => 'required|string',
            'pnm_message' => 'required|string',
            'pnm_link' => 'nullable|string',
            'pnm_icon' => 'nullable|string',
            'pnm_hash_id_location' => 'nullable|string',
            'pnm_start_date' => 'nullable|date',
            'pnm_end_date' => 'nullable|date',
            'pnm_type' => 'nullable|string',
            // 'pnm_notif_loc' => 'nullable|string'
        ]);

        $insert = PortalNotif::create([
            'p_u_username' => $request->pnm_from_users,
            'pnm_to_users' => $request->pnm_to_users,
            'pnm_title' => $request->pnm_title,
            'pnm_content' => $request->pnm_message,
            'pnm_action_url' => $request->pnm_link,
            'pnm_icon' => $request->pnm_icon,
            'pnm_hash_id_location' => $request->pnm_hash_id_location ?? null,
            'pnm_start_date' => $request->pnm_start_date,
            'pnm_end_date' => $request->pnm_end_date,
            'pnm_type' => $request->pnm_type,
            'pnm_notif_loc' => $request->pnm_notif_loc ?? 'portal'
        ]);

        if ($request->pnm_notif_loc == 'teams') {
            $this->sendTeamsNotification(new Request($request->graph));
        }

        return $this->handleResponse($insert, 'Notification Created !');
    }
    
    public function sendTeamsNotification(Request $request)
    {
        logger()->info('sendTeamsNotification called with request: ' . json_encode($request->all()));
        $accessToken = $request->accessToken;
        $messageContent = $request->message;
        $recipientId = $request->userID; // The user ID or UPN of the person you're messaging

        // --- FIX 1: Get the Sender's User ID ---
        // You need the ID of the user/app making the API call.
        // The most reliable way is to get it from the '/me' endpoint.
        $senderResponse = Http::withToken($accessToken)->get("https://graph.microsoft.com/v1.0/me");

        if ($senderResponse->failed()) {
            return response()->json(['status' => 'error', 'message' => 'Failed to get sender details.', 'details' => $senderResponse->json()], 400);
        }
        $senderId = $senderResponse->json()['id'];

        // --- Create the Chat ---
        $createChatResponse = Http::withToken($accessToken)
            ->post("https://graph.microsoft.com/v1.0/chats", [
                'chatType' => 'oneOnOne',
                'members' => [
                    // Member 1: The Sender (logged-in user)
                    [
                        '@odata.type' => '#microsoft.graph.aadUserConversationMember',
                        'roles' => ['owner'],
                        'user@odata.bind' => "https://graph.microsoft.com/v1.0/users('{$senderId}')"
                    ],
                    // Member 2: The Recipient
                    [
                        '@odata.type' => '#microsoft.graph.aadUserConversationMember',
                        'roles' => ['owner'],
                        'user@odata.bind' => "https://graph.microsoft.com/v1.0/users('{$recipientId}')"
                    ]
                ]
            ]);

        if ($createChatResponse->failed()) {
            // Correctly return the error from this specific call
            return response()->json(['status' => 'error', 'message' => 'Failed to create chat.', 'details' => $createChatResponse->json()], 400);
        }

        $chatId = $createChatResponse->json()['id'];

        // --- Send the Message to the newly created chat ---
        $messageResponse = Http::withToken($accessToken)
            ->post("https://graph.microsoft.com/v1.0/chats/{$chatId}/messages", [
                'body' => [
                    'contentType' => 'html', // Or 'text'
                    'content' => $messageContent
                ]
            ]);

        // --- FIX 2: Correct Error Handling for the Second Call ---
        if ($messageResponse->successful()) {
            logger()->info('Message sent successfully: ' . $messageResponse->body());
            return response()->json(['status' => 'success', 'message' => 'Notification sent successfully.']);
        } else {
            logger()->error('Failed to send message: ' . $messageResponse->body());
            // If sending the message fails, return its specific error
            return response()->json(['status' => 'error', 'message' => 'Chat created, but failed to send message.', 'details' => $messageResponse->json()], 400);
        }
    }


    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $req, $id)
    {
        $hasil = PortalNotif::where('id', $id)->first();
        $deleteShare = FormShareDet::where('cfsd_gen_link', $hasil->pnm_hash_id_location)->where('p_u_username', $req->header('username'))->delete();
        $deleteNotif = PortalNotif::where('id', $id)->delete();
        return $this->handleResponse([
            'dataNotif' => $hasil,
            'delete_share' => $deleteShare,
            'delete_notif' => $deleteNotif
        ], 'Data Deleted !');
    }
}
