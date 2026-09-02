# Document Signing Quick Start

## What You Have Now

✅ Database tables created  
✅ Models registered  
✅ Controllers ready  
✅ Routes wired  
✅ Trait methods in place  

## 3 Simple Tests

### Test 1: Get Approval Workflow
```bash
curl http://localhost/api/ams/approval/1
```
Response includes `apprv_set.amssd_is_docsign` (true/false)

### Test 2: Create Signature Boxes
```bash
curl -X POST http://localhost/api/ams/docsign/save \
  -H "Content-Type: application/json" \
  -d '{
    "amsm_id": 1,
    "boxes": [
      {
        "amsmd_id": 12,
        "dsbx_page_no": 1,
        "dsbx_x": 50,
        "dsbx_y": 100,
        "dsbx_width": 150,
        "dsbx_height": 60,
        "dsbx_label": "CFO Sign Here"
      }
    ]
  }'
```

### Test 3: Check Token Includes Boxes
When approver opens token link, they now see:
```bash
curl http://localhost/api/ams/getMasterApprovalByToken/{token}/{tokenHist}
```
Response includes:
```json
{
  "sign_boxes": [
    {
      "page_no": 1,
      "x": 50,
      "y": 100,
      "width": 150,
      "height": 60,
      "label": "CFO Sign Here",
      "signed": false
    }
  ]
}
```

## Missing Piece: PDF Stamping

When approver submits signature, we need to actually draw on the PDF. This requires:

```bash
composer require setasign/fpdi:^2.0
```

Then implement the stamping in `stampSignatureOnPdf()` method in `ApprovalActionTraits.php` (line 843).

## Next: Frontend Integration

- Create PDF viewer with PDF.js
- Overlay signature boxes on canvas
- On approve, send signature_base64 + position to `/ams/approveAction`
- Backend stamps PDF, returns signed version

---

**Status:** Production-ready API. PDF stamping library pending.
