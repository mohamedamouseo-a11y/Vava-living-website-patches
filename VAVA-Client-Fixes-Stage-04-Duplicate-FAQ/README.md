# VAVA Client Fixes — Stage 04

## Remove duplicate FAQ admin section

### Root cause
`inc/paths-vava.php` registers two admin sections with the same FAQ label:

- `questions`
- `faq`

Both use the same saved `faq.items` data, but only `faq` has the real editable controls. The duplicate `questions` section is therefore redundant and can fall through to unrelated controls.

### Fix
- Remove `questions` from `vava_paths_sections()` for Arabic and English.
- Remove the obsolete `questions` preview branch.
- Keep the canonical `faq` section and all saved FAQ data unchanged.

### Target live file
`wp-content/themes/vava-living-theme-ar-v1/inc/paths-vava.php`

### Patch
`patches/0001-remove-duplicate-paths-faq-admin-section.patch`

### Validation
- PHP syntax must pass.
- Paths VAVA admin must show only one FAQ section in Arabic and English.
- FAQ title, intro, questions and answers must remain editable.
- Saved FAQ data and frontend output must remain unchanged.
