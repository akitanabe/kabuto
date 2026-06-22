# Repository Guidelines

## Project Structure & Module Organization

Kabuto is a PHP template engine library. Runtime and compiler code lives in `src/`
under the `Kabuto\` PSR-4 namespace. Key areas include `src/Parser/`,
`src/Ast/`, and `src/Compiler/`. Tests live in `tests/` under the
`Kabuto\Tests\` namespace, with reusable test-only components in
`tests/Fixtures/`.

## Build, Test, and Development Commands

Run commands through Composer from the repository root:

```sh
composer install        # install PHP dependencies
composer test           # run PHPUnit tests
composer analyse        # run PHPStan at max level
composer lint           # run Mago lint checks
composer format         # format src/ and tests/
composer format:check   # verify formatting without changing files
composer check          # run tests, analysis, lint, and format check
```

Use `composer check` before opening a pull request when possible. `composer
analyse` may require elevated permissions in restricted environments.

## Coding Style & Naming Conventions

Use PHP 8.5 features where they improve clarity. For consecutive transformations,
prefer the pipe operator, for example `"Hello World" |> strtoupper(...)`, when it
reads better than nested calls. Follow PSR-4 organization: one primary class or
interface per file, with the path matching its namespace. Keep names descriptive:
parser classes describe what they parse, renderer classes what they render, and
AST node classes end in `Node`. Format with Mago rather than manual style-only
edits. Add a brief purpose comment to new functions or methods when intent is not
obvious from the signature.

## Testing Guidelines

PHPUnit 12 is the test framework. Use PHPUnit attributes such as `#[Test]`
instead of docblock annotations. Add or update tests for externally observable
behavior before changing implementation details. Prefer the closest existing test
file, such as `tests/ParserTest.php` or `tests/TemplateEngineTest.php`. Name
tests with clear behavior-oriented method names and place shared fixtures in
`tests/Fixtures/` only when they are reused.

## Commit & Pull Request Guidelines

Recent commits use short imperative summaries, for example `Implement phase 1
synchronous runtime` and `Implement synchronous Resource`. Keep subjects concise
and focused on behavior. Pull requests should include a short description,
verification commands, and any relevant issue or design note. For rendering
changes, include a compact before/after example or failing template case.

## Security & Configuration Tips

Do not commit generated caches such as `.phpunit.cache/` or dependency
directories such as `vendor/`. Keep escaping behavior centralized through the
existing runtime APIs, and add regression tests for any change that affects HTML
output, attributes, slots, or component props.
