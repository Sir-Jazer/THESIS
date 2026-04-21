## Plan: Student, Cashier, Proctor Rollout

This plan is designed for continuation across chats.

## Scope and Session Split
- Session 1: Student + Cashier core features
- Session 2: Proctor core features
- Out of scope for now: Notifications and E-Payment Receipts

## Current Status Snapshot (as of April 21, 2026)
Completed foundation work:
- Added permit and attendance schema
- Added shared portal services for timeline, subject resolution, permit state, and cashier rows
- Added student routes/pages for My Subjects and My Permit
- Added cashier routes/pages for Student Payments and permit generate/revoke actions
- Added QR package and rendered student permit QR
- Updated sidebar navigation and refreshed student/cashier dashboards
- Validated app boot, route listing, and blade compilation

Pending before moving to Session 2:
- Run migrations in your local DB environment
- Add focused feature tests for Session 1
- Optional UI refinement pass to exactly match your latest screenshots

## Detailed Execution Plan

### Phase 1: Shared Contracts and Persistence
1. Confirm shared data contracts for all portals:
   - Academic timeline from AcademicSetting
   - Published section schedules from SectionExamSchedule and SectionExamScheduleSlot
   - Student-section linkage from StudentProfile and Section
   - Proctor-slot assignment from section_exam_schedule_slot_proctors
   - Advisory linkage from Section.proctor_id
2. Add permit persistence:
   - One permit per student per academic_year + semester + exam_period
   - Fields: token, generated_by, generated_at, revoked_at, is_active
3. Add attendance persistence:
   - One attendance per student per schedule slot
   - Unique constraint on slot + student to prevent duplicates

### Phase 2 (Session 1): Student + Cashier
4. Student portal
   - My Subjects: active timeline, period navigation from Prelim to Finals even with empty periods
   - Status: Pending if no attendance, Cleared if attendance exists
5. Student My Permit
   - Show no QR before permit generation
   - Show active QR after cashier generation
   - One QR reusable across all enrolled subjects in the same exam period
6. Cashier Student Payments
   - List enrolled students
   - Add filters (search, program, year level)
   - Actions: Generate Permit, Revoke Permit
7. Keep dashboards lightweight
   - Student: summary counts/context
   - Cashier: permit counts/context

### Phase 3 (Session 2): Proctor
8. QR Scanner workflow
   - Proctor selects assigned section and assigned subject/slot first
   - Scan and validate:
     - active timeline
     - student belongs to section
     - active permit exists for period
     - student scheduled for selected slot
     - no duplicate attendance for same slot
   - Log attendance and update student-side status to Cleared
9. Proctor Exam Schedules
   - Period navigation
   - Rename Action column to Section Attendance
   - Rename button to View Table
   - Show attendance table under schedules for selected slot
10. Proctor Advisees
   - Show advisory students using Section.proctor_id linkage
11. Pending Registrations
   - Reuse same approve/reject logic as system admin
   - Avoid duplicate logic by extracting shared service/action if needed

### Phase 4: Verification
12. Session 1 verification
   - Timeline filtering for student pages
   - Permit visibility before/after generation
   - Generate/revoke/regenerate behavior
13. Session 2 verification
   - Scanner validation errors and success paths
   - Duplicate scan prevention
   - Student status change after attendance logging
   - Proctor pending-registration parity with admin

## Relevant Files
- webapp/routes/web.php
- webapp/app/Models/AcademicSetting.php
- webapp/app/Models/StudentProfile.php
- webapp/app/Models/Section.php
- webapp/app/Models/SectionExamSchedule.php
- webapp/app/Models/SectionExamScheduleSlot.php
- webapp/app/Services/AcademicHead/ScheduleService.php
- webapp/app/Services/Portal/ExamPortalService.php
- webapp/app/Services/Portal/ExamPermitService.php
- webapp/app/Http/Controllers/Student/DashboardController.php
- webapp/app/Http/Controllers/Student/SubjectController.php
- webapp/app/Http/Controllers/Student/PermitController.php
- webapp/app/Http/Controllers/Cashier/DashboardController.php
- webapp/app/Http/Controllers/Cashier/StudentPaymentController.php
- webapp/resources/views/layouts/sidebar.blade.php
- webapp/resources/views/student/dashboard.blade.php
- webapp/resources/views/student/subjects/index.blade.php
- webapp/resources/views/student/permit/show.blade.php
- webapp/resources/views/cashier/dashboard.blade.php
- webapp/resources/views/cashier/student-payments/index.blade.php
- webapp/database/migrations/2026_04_21_000201_create_exam_permits_table.php
- webapp/database/migrations/2026_04_21_000202_create_exam_attendances_table.php

## Decisions Locked
- Session split stays: Session 1 student+cashier, Session 2 proctor
- Notifications remain excluded for this cycle
- Permit lifecycle stays one QR per student per exam period
- Proctor has same pending-registration approval capability as system admin
- Cashier generation is temporary clearance authority until accounting rules are defined

## Commands Reminder
Run app server:
- cd C:\THESIS\webapp
- C:\xampp\php\php.exe .\artisan serve

Run frontend build if assets changed:
- Push-Location c:\THESIS\webapp; & npm.cmd run build
