# Contributing

Merci ! Contributions in English or French are welcome. Parsing and sync-engine changes belong in [yeevy/centris-passerelle](https://github.com/yeevy-ai/centris-passerelle) — this repo is the Laravel integration layer.

## Setup

```bash
composer install
composer test      # Pest (orchestra/testbench)
composer analyse   # larastan level 8
composer format    # Pint
```

All three must pass — CI enforces them across PHP 8.2–8.4 and Laravel 12/13, with both lowest and stable dependencies.

## The one hard rule

**Never commit real feed data or credentials.** No real Passerelle rows, MLS numbers, broker names, or FTP credentials — in fixtures, tests, examples, or commit messages. Fixtures are entirely synthetic; `.gitignore` blocks `*.TXT` as a safety net.

## Style

- Small, focused commits — one logical change each.
- New behavior comes with testbench tests.
- Keep the wrapper thin: if a class has no `Illuminate\*` import, it probably belongs in the core package.
