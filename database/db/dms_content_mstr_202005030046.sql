INSERT INTO DMS.dbo.dms_content_mstr (content_title,content_html,created_at,updated_at) VALUES 
('Circular Technical','<h3>CIRCULAR {id}</h3>
<p>&nbsp;</p>
<h3>Date {date}</h3>
<h3>Subject {subject}</h3>
<p>&nbsp;</p>
<p><strong>1). Model</strong></p>
<p>{model}</p>
<p>&nbsp;</p>
<p><strong>2). Content</strong></p>
<table style="border-collapse: collapse; width: 100%;" border="1">
<tbody>
<tr>
<td style="width: 50%;" colspan="2">{content}</td>
</tr>
<tr>
<td style="width: 50%;">Filing No</td>
<td style="width: 50%;">{filno}</td>
</tr>
<tr>
<td style="width: 50%;">Location</td>
<td style="width: 50%;">{loc}</td>
</tr>
<tr>
<td style="width: 50%;">Description</td>
<td style="width: 50%;">{desc}</td>
</tr>
<tr>
<td style="width: 50%;">Part Name</td>
<td style="width: 50%;">{partname}</td>
</tr>
<tr>
<td style="width: 50%;">Part Code</td>
<td style="width: 50%;">{partcode}</td>
</tr>
<tr>
<td style="width: 50%;">Part Type</td>
<td style="width: 50%;">{parttype}</td>
</tr>
<tr>
<td style="width: 50%;">Vender</td>
<td style="width: 50%;">{vender}</td>
</tr>
</tbody>
</table>
<p>&nbsp;</p>
<h3>&nbsp;</h3>
<p>&nbsp;</p>
<p>Execution Schedule : {exec}</p>
<p>[Reason] : {reason}</p>','2020-04-24 09:31:36.017','2020-04-30 13:44:18.820')
,('Technical Notes','<h4><strong>CIRCULAR {ID:string}</strong></h4>
<table style="border-collapse: collapse; width: 35.2688%; height: 41px;" border="1">
<tbody>
<tr style="height: 20px;">
<td style="width: 33.3333%; height: 20px;"><strong>Date</strong></td>
<td style="width: 100%; height: 20px;"><strong>: {Date:date}</strong></td>
</tr>
<tr style="height: 21px;">
<td style="width: 33.3333%; height: 21px;"><strong>Subject</strong></td>
<td style="width: 33.3333%; height: 21px;"><strong>: {Subject Header}</strong></td>
</tr>
</tbody>
</table>
<p>&nbsp;</p>
<h4><strong>Please confirm and follow the technical notice.</strong></h4>
<h4><strong>Contents</strong></h4>
<p><strong>-</strong></p>
<p><strong>1. Applicable</strong></p>
<table style="border-collapse: collapse; width: 100%;" border="1">
<tbody>
<tr>
<td style="width: 33.3333%;">Filling No.</td>
<td style="width: 33.3333%;">Spec.</td>
<td style="width: 33.3333%;">Assy No.</td>
</tr>
<tr>
<td style="width: 33.3333%;">{Filling No App}</td>
<td style="width: 33.3333%;">{Spec}</td>
<td style="width: 33.3333%;">{Assy No}</td>
</tr>
</tbody>
</table>
<p>&nbsp;</p>
<p><strong>2. Contents</strong></p>
<table style="border-collapse: collapse; width: 100%; height: 144px;" border="1">
<tbody>
<tr style="height: 18px;">
<td style="width: 50%; height: 18px;" colspan="2">{Subjects Content}</td>
</tr>
<tr style="height: 18px;">
<td style="width: 50%; height: 18px;">Filing No</td>
<td style="width: 50%; height: 18px;">{Filling No Cont}</td>
</tr>
<tr style="height: 18px;">
<td style="width: 50%; height: 18px;">Location</td>
<td style="width: 50%; height: 18px;">{Location}</td>
</tr>
<tr style="height: 18px;">
<td style="width: 50%; height: 18px;">Description</td>
<td style="width: 50%; height: 18px;">{Description}</td>
</tr>
<tr style="height: 18px;">
<td style="width: 50%; height: 18px;">Part Name</td>
<td style="width: 50%; height: 18px;">{Part Name}</td>
</tr>
<tr style="height: 18px;">
<td style="width: 50%; height: 18px;">Part Code</td>
<td style="width: 50%; height: 18px;">{Part Code}</td>
</tr>
<tr style="height: 18px;">
<td style="width: 50%; height: 18px;">Part Type</td>
<td style="width: 50%; height: 18px;">{Part Type}</td>
</tr>
<tr style="height: 18px;">
<td style="width: 50%; height: 18px;">Vender</td>
<td style="width: 50%; height: 18px;">{Vender}</td>
</tr>
</tbody>
</table>
<p><strong>3. Issued Documents</strong></p>
<p>|list_attached_doc_here|</p>
<table style="border-collapse: collapse; width: 100%; height: 90px;" border="1">
<tbody>
<tr style="height: 18px;">
<td style="width: 50%; height: 18px;" colspan="2">Lot I/D : {Lot ID}</td>
</tr>
<tr style="height: 18px;">
<td style="width: 50%; height: 18px;" colspan="2">Execution schedule : {Execution Sch}</td>
</tr>
<tr style="height: 18px;">
<td style="width: 50%; height: 18px;" colspan="2">Measures of old part : {Measure}</td>
</tr>
<tr style="height: 18px;">
<td style="width: 50%; height: 18px;" colspan="2">[Reason] {Reason}</td>
</tr>
<tr style="height: 18px;">
<td style="width: 50%; height: 18px;">[Related Document] {Related Doc}</td>
<td style="width: 50%; height: 18px;">[Requested By] {Request By}</td>
</tr>
</tbody>
</table>','2020-04-28 12:19:18.867','2020-04-30 13:43:22.927')
;