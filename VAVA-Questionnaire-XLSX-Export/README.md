# VAVA — Questionnaire True XLSX Export

Work only on the CURRENT live `/public_html`.

## Goal
Fix Arabic mojibake in questionnaire downloads by changing the existing questionnaire export from CSV to a real Excel `.xlsx` file that opens directly in Microsoft Excel with Arabic text displayed correctly.

## Scope
Target only the questionnaire export implementation currently used by the three export buttons:
- Beginning
- Midpoint
- Impact

Primary live file is expected to be:
`wp-content/themes/vava-living-theme-ar-v1/inc/vava-impact-export.php`
Use the current live implementation as source of truth.

Do not redesign the questionnaire UI or change the existing buttons unless the filename/extension needs updating.

## Preserve current export behavior
Keep exactly the current data logic that was already fixed:
- export actual saved submissions only
- booking/customer/service/session/status/language/submitted metadata
- question columns from the saved questionnaire snapshot
- answer mapping from `data['answers'][field_id]`
- radio/checkbox values translated using snapshot labels and submission language
- text/textarea/number values preserved
- formula-injection protection
- per-type filtering for beginning/midpoint/impact
- current permissions/nonces/access checks

## Required output
Generate a valid Office Open XML workbook:
- extension: `.xlsx`
- MIME: `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet`
- worksheet contains the same headers and rows as the current CSV export
- Arabic and English must be stored as Unicode XML and display correctly when opened directly in Excel
- use strings, not formulas, for user-provided cell values

Prefer a small self-contained XLSX writer using PHP `ZipArchive` + minimal OOXML if no existing XLSX library is already available. Do NOT add Composer/vendor packages or heavy dependencies.

If `ZipArchive` is unavailable on the live server, fail safely with a clear admin error instead of silently falling back to broken CSV.

## XLSX minimum structure
Create a standards-valid workbook containing the required OOXML parts (content types, relationships, workbook, worksheet, styles if needed). Inline strings are acceptable and preferred to avoid shared-string complexity.

Sanitize XML control characters and escape XML safely.

## Validation
- Use one real Midpoint submission (Booking 179 if still available) and confirm Arabic question/answer text is present in the generated workbook XML.
- Confirm downloaded filename ends in `.xlsx`.
- Confirm response Content-Type is XLSX.
- Confirm the workbook can be opened successfully by Excel-compatible tooling if available.
- Run PHP lint if available.
- Run `git diff --check`.

Do not touch bookings logic, questionnaire definitions, product code, PDF, payments, logo, Paths, Developer Hub, or unrelated systems.
Do not push, merge, commit, or create a PR.
Leave changes only in `/public_html` for review.
