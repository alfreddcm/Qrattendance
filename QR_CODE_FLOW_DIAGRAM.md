# QR CODE REGENERATION FLOW DIAGRAM

## System Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                    QR CODE DATA STRUCTURE                        │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  QR DATA: {id_no}_{random_10_chars}                            │
│           └──┬───┘ └────────┬────────┘                         │
│              │              │                                    │
│         CRITICAL      Random String                             │
│                    (AB12CD34EF)                                 │
│                                                                  │
│  FILE NAME: qr_codes/{id_no}_{sanitized_name}.svg              │
│                      └──┬───┘ └──────┬──────┘                  │
│                         │             │                          │
│                    CRITICAL      CRITICAL                        │
│                                                                  │
│  DATABASE:                                                       │
│  ├─ qr_code: "qr_codes/2123123_John_Doe.svg"                   │
│  └─ stud_code: "2123123_AB12CD34EF"                            │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

---

## Update Flow Diagram

```
┌──────────────────────────────────────────────────────────────────┐
│                    USER EDITS STUDENT                             │
└──────────────────┬───────────────────────────────────────────────┘
                   │
                   ▼
        ┌──────────────────────┐
        │  What changed?       │
        └──────┬───────────────┘
               │
       ┌───────┴────────┐
       │                │
       ▼                ▼
┌──────────────┐  ┌──────────────────┐
│ Non-Critical │  │ Critical Fields  │
│   Fields     │  │ (id_no or name)  │
│              │  │                  │
│ • age        │  │ ✓ id_no          │
│ • gender     │  │ ✓ name           │
│ • address    │  │                  │
│ • cp_no      │  │                  │
│ • section_id │  │                  │
│ • etc.       │  │                  │
└──────┬───────┘  └────────┬─────────┘
       │                   │
       ▼                   ▼
┌──────────────┐  ┌────────────────────┐
│   Update     │  │  Has QR Code?      │
│   Normally   │  └────────┬───────────┘
│              │           │
│  No Alert    │    ┌──────┴──────┐
│  No QR       │    │             │
│  Changes     │    ▼             ▼
└──────────────┘  ┌─────┐    ┌────────┐
                  │ No  │    │  Yes   │
                  └──┬──┘    └────┬───┘
                     │            │
                     ▼            ▼
              ┌──────────┐  ┌──────────────────┐
              │  Update  │  │  SWEETALERT #1   │
              │ Normally │  │                  │
              └──────────┘  │ "QR Will Be      │
                            │  Deleted"        │
                            │                  │
                            │ Shows:           │
                            │ • Changed fields │
                            │ • Explanation    │
                            │ • Reason why     │
                            │                  │
                            │ [Cancel]         │
                            │ [Update & Delete]│
                            └────────┬─────────┘
                                     │
                              ┌──────┴──────┐
                              │             │
                              ▼             ▼
                         ┌────────┐   ┌─────────────────┐
                         │ Cancel │   │  SWEETALERT #2  │
                         │        │   │                 │
                         │   No   │   │ "Regenerate QR  │
                         │ Changes│   │   Now?"         │
                         └────────┘   │                 │
                                      │ [No, Later]     │
                                      │ [Yes, Generate] │
                                      └────────┬────────┘
                                               │
                                        ┌──────┴──────┐
                                        │             │
                                        ▼             ▼
                              ┌──────────────┐  ┌──────────────┐
                              │   No Later   │  │ Yes Generate │
                              ├──────────────┤  ├──────────────┤
                              │              │  │              │
                              │ 1. Update    │  │ 1. Update    │
                              │    student   │  │    student   │
                              │ 2. Delete QR │  │ 2. Delete QR │
                              │ 3. Set NULL  │  │ 3. Generate  │
                              │              │  │    NEW QR    │
                              │ Message:     │  │              │
                              │ "Please      │  │ Message:     │
                              │  regenerate  │  │ "Updated +   │
                              │  QR code"    │  │  QR created!"│
                              └──────────────┘  └──────────────┘
```

---

## Critical vs Non-Critical Fields

```
┌─────────────────────────────────────────────────────────────┐
│                    STUDENT FIELDS                            │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  CRITICAL FIELDS (Affect QR Code)                           │
│  ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━                          │
│                                                              │
│  ┌──────────────────────────────────────────────────┐      │
│  │  id_no                                            │      │
│  │  ├─ Used in: QR Data (2123123_AB12CD34EF)       │      │
│  │  ├─ Used in: File Name                           │      │
│  │  └─ Impact: ⚠️ CRITICAL - Invalidates QR data   │      │
│  └──────────────────────────────────────────────────┘      │
│                                                              │
│  ┌──────────────────────────────────────────────────┐      │
│  │  name                                             │      │
│  │  ├─ Used in: File Name (John_Doe.svg)           │      │
│  │  └─ Impact: ⚠️ CRITICAL - Breaks file path      │      │
│  └──────────────────────────────────────────────────┘      │
│                                                              │
│  ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━        │
│                                                              │
│  NON-CRITICAL FIELDS (Safe to Change)                       │
│  ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━                          │
│                                                              │
│  ✓ age                  ✓ contact_person_name               │
│  ✓ gender               ✓ contact_person_relationship       │
│  ✓ address              ✓ contact_person_contact            │
│  ✓ cp_no                ✓ school_year_id                    │
│  ✓ picture              ✓ section_id                        │
│                                                              │
│  Impact: ✅ Safe - QR code remains valid                    │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

---

## File System Structure

```
storage/
└── app/
    └── public/
        └── qr_codes/
            ├── 2123123_John_Doe.svg          ← QR for John Doe
            ├── 2123124_Jane_Smith.svg        ← QR for Jane Smith
            ├── 2123125_Pedro_Garcia.svg      ← QR for Pedro Garcia
            └── ...

public/
└── storage/                                   ← Symlink
    └── qr_codes/                              ← Same files (symlinked)
        ├── 2123123_John_Doe.svg
        └── ...

database (students table):
┌────┬────────┬─────────────┬───────────────────────────────┬──────────────────┐
│ id │ id_no  │ name        │ qr_code                       │ stud_code        │
├────┼────────┼─────────────┼───────────────────────────────┼──────────────────┤
│ 1  │2123123 │ John Doe    │qr_codes/2123123_John_Doe.svg  │2123123_AB12CD34EF│
│ 2  │2123124 │ Jane Smith  │qr_codes/2123124_Jane_Smith.svg│2123124_XY98ZW12AB│
│ 3  │2123125 │ Pedro Garcia│qr_codes/2123125_Pedro_Garcia..│2123125_MN45OP67QR│
└────┴────────┴─────────────┴───────────────────────────────┴──────────────────┘
```

---

## QR Code Lifecycle

```
┌────────────────────────────────────────────────────────────────┐
│                      CREATION                                   │
├────────────────────────────────────────────────────────────────┤
│                                                                 │
│  1. Generate random string:                                     │
│     substr(str_shuffle('A-Z0-9'), 0, 10) → "AB12CD34EF"        │
│                                                                 │
│  2. Create QR data:                                             │
│     id_no + "_" + random → "2123123_AB12CD34EF"                │
│                                                                 │
│  3. Generate QR image:                                          │
│     QrCode::format('svg')->size(200)->generate(data)           │
│                                                                 │
│  4. Create filename:                                            │
│     "qr_codes/" + id_no + "_" + sanitize(name) + ".svg"        │
│     → "qr_codes/2123123_John_Doe.svg"                          │
│                                                                 │
│  5. Save file:                                                  │
│     Storage::disk('public')->put(path, image)                  │
│                                                                 │
│  6. Update database:                                            │
│     qr_code = "qr_codes/2123123_John_Doe.svg"                  │
│     stud_code = "2123123_AB12CD34EF"                           │
│                                                                 │
└────────────────────────────────────────────────────────────────┘

┌────────────────────────────────────────────────────────────────┐
│                      UPDATE (Critical Change)                   │
├────────────────────────────────────────────────────────────────┤
│                                                                 │
│  IF id_no OR name changed AND student has QR:                  │
│                                                                 │
│  1. Show SweetAlert warning                                     │
│  2. User confirms update                                        │
│  3. Delete old QR file from storage                            │
│  4. Set qr_code = NULL                                          │
│  5. Set stud_code = NULL                                        │
│  6. Ask to regenerate now                                       │
│  7a. If YES → Generate new QR (go to CREATION)                 │
│  7b. If NO → Show reminder message                             │
│                                                                 │
└────────────────────────────────────────────────────────────────┘

┌────────────────────────────────────────────────────────────────┐
│                      DELETION                                   │
├────────────────────────────────────────────────────────────────┤
│                                                                 │
│  clearStudentQrCode($student):                                 │
│                                                                 │
│  1. Check if qr_code exists in database                        │
│  2. Delete file: Storage::disk('public')->delete(qr_code)      │
│  3. Delete legacy files (old naming patterns)                  │
│  4. Set qr_code = NULL in database                             │
│  5. Set stud_code = NULL in database                           │
│                                                                 │
└────────────────────────────────────────────────────────────────┘
```

---

## Example Scenario: Changing Student Name

```
BEFORE:
┌──────────────────────────────────────────┐
│ Student: John Doe                         │
│ ID No: 2123123                            │
│ QR Code: ✓ EXISTS                         │
│                                           │
│ Database:                                 │
│ └─ qr_code: "qr_codes/2123123_John_Doe.svg"
│ └─ stud_code: "2123123_AB12CD34EF"        │
│                                           │
│ File System:                              │
│ └─ storage/.../qr_codes/2123123_John_Doe.svg
└──────────────────────────────────────────┘

USER ACTION: Change name to "John Smith"
                    ↓
                    
┌──────────────────────────────────────────┐
│        🔔 SWEETALERT WARNING              │
├──────────────────────────────────────────┤
│                                           │
│  QR Code Will Be Deleted                  │
│                                           │
│  You changed:                             │
│  • Name: "John Doe" → "John Smith"        │
│                                           │
│  ⚠️ QR code deleted because:             │
│  • File name must match student info     │
│                                           │
│  ✓ You can regenerate after update       │
│                                           │
│    [Cancel]  [Update & Delete QR]         │
└──────────────────────────────────────────┘

USER CONFIRMS:
                    ↓
                    
┌──────────────────────────────────────────┐
│     🔄 REGENERATE QR NOW?                 │
├──────────────────────────────────────────┤
│                                           │
│  Would you like to generate a new         │
│  QR code immediately?                     │
│                                           │
│  [No, I'll do it later]  [Yes, Generate]  │
└──────────────────────────────────────────┘

IF USER SELECTS "YES, GENERATE":
                    ↓

AFTER (Auto-regenerated):
┌──────────────────────────────────────────┐
│ Student: John Smith                       │
│ ID No: 2123123                            │
│ QR Code: ✓ NEW QR GENERATED               │
│                                           │
│ Database:                                 │
│ └─ qr_code: "qr_codes/2123123_John_Smith.svg"
│ └─ stud_code: "2123123_XY98ZW12AB" (NEW) │
│                                           │
│ File System:                              │
│ ├─ ❌ 2123123_John_Doe.svg (DELETED)      │
│ └─ ✓ 2123123_John_Smith.svg (CREATED)    │
│                                           │
│ Message: "Updated + QR created!"          │
└──────────────────────────────────────────┘

IF USER SELECTS "NO, I'LL DO IT LATER":
                    ↓

AFTER (Manual regeneration needed):
┌──────────────────────────────────────────┐
│ Student: John Smith                       │
│ ID No: 2123123                            │
│ QR Code: ❌ NONE                          │
│                                           │
│ Database:                                 │
│ └─ qr_code: NULL                          │
│ └─ stud_code: NULL                        │
│                                           │
│ File System:                              │
│ └─ ❌ 2123123_John_Doe.svg (DELETED)      │
│                                           │
│ Message: "Updated. Please regenerate QR" │
└──────────────────────────────────────────┘
```

---

## Key Takeaways

✅ **QR codes are automatically deleted** when ID or Name changes
✅ **Users are warned** before deletion with clear explanation  
✅ **Option to regenerate** immediately or later
✅ **Non-critical fields** can be updated freely without affecting QR
✅ **System maintains data integrity** - QR always matches student info
