# QR CODE SYSTEM - QUICK REFERENCE

## 📋 QR Code Components

### Data Structure
```
QR Data:    {id_no}_{random_10_chars}
Example:    2123123_AB12CD34EF

File Name:  qr_codes/{id_no}_{sanitized_name}.svg
Example:    qr_codes/2123123_John_Doe.svg

DB Fields:  
  - qr_code:   File path
  - stud_code: QR data
```

---

## ⚠️ Critical Fields (Trigger QR Deletion)

| Field | Used In | Impact |
|-------|---------|--------|
| **id_no** | QR Data + Filename | QR becomes invalid |
| **name** | Filename | File path breaks |

**When changed**: QR deleted → User prompted to regenerate

---

## ✅ Safe Fields (No QR Impact)

- age
- gender  
- address
- cp_no
- picture
- section_id
- school_year_id
- contact_person_* (all)

**When changed**: Update normally, QR unchanged

---

## 🔄 Regeneration Flow

```
1. Edit student with QR
2. Change id_no or name
3. Click "Update"
4. ⚠️ Alert: "QR Will Be Deleted" → [Confirm]
5. ❓ Alert: "Regenerate Now?" → [Yes/No]
6. ✓ If Yes: New QR created
   ✓ If No: Manual regeneration needed
```

---

## 💻 Code Locations

### Frontend
**File**: `resources/views/teacher/edit_student.blade.php`
- Lines 1-7: SweetAlert2 CDN
- Lines 154-160: Hidden tracking fields
- Lines 428-530: Detection & SweetAlert logic

### Backend  
**File**: `app/Http/Controllers/StudentManagementController.php`
- Lines 1-73: QR System Documentation
- Lines 305-345: Update method with detection
- Lines 606-704: generateQrForStudent()
- Lines 881-900: clearStudentQrCode()

---

## 🧪 Testing Commands

```bash
# Check if QR exists
php artisan tinker
>>> $student = App\Models\Student::find(1);
>>> $student->qr_code;
>>> $student->stud_code;

# Check file exists
>>> Storage::disk('public')->exists($student->qr_code);

# Manually clear QR
>>> $student->update(['qr_code' => null, 'stud_code' => null]);
```

---

## 🐛 Troubleshooting

### QR not showing after generation
```bash
# Ensure storage link exists
php artisan storage:link

# Check permissions
chmod -R 755 storage/app/public/qr_codes
```

### Old QR still showing
```bash
# Clear cache
php artisan cache:clear
php artisan view:clear

# Regenerate QR from UI
```

### SweetAlert not appearing
- Check browser console for errors
- Verify SweetAlert2 CDN loaded
- Check `has_qr_code` hidden field value

---

## 📝 Change Log

**Version 1.0** - Initial Implementation
- ✅ Auto-detection of critical field changes
- ✅ SweetAlert warnings before QR deletion
- ✅ Option to regenerate immediately
- ✅ Comprehensive documentation
- ✅ Non-critical field safety

**Future Enhancements**
- [ ] Bulk update detection
- [ ] QR change history
- [ ] QR preview before confirming
- [ ] Auto-regeneration setting
