---
title: Development
---

# Development

This guide describes the local workflow for contributing to Sire, including tests, static analysis, coverage, and documentation checks.

## Set up the project

Start by cloning the repository and installing dependencies.

1. Clone the repository.
2. Install dependencies.

    ```bash
    composer install
    ```

3. Run the test suite once to verify your environment.

    ```bash
    composer test
    ```

## Run quality checks

Sire uses Composer scripts for all routine quality checks.

- Run tests: `composer test`
- Run static analysis: `composer types`
- Run markdown linting: `composer mdlint`
- Run path coverage and line coverage checks: `composer coverage`
- Run the full local pipeline: `composer ci`

Use `composer ci` before opening a pull request so your branch matches the project quality baseline.

## Work on documentation

When you update `README.md` or files in `docs/`, run markdown linting to keep formatting consistent.

1. Edit the relevant markdown files.
2. Run markdown checks.

    ```bash
    composer mdlint
    ```

3. Fix reported issues before committing.

## Use a release-safe workflow

Small, focused commits make reviews and releases easier to manage.

- Keep each commit scoped to one logical change.
- Write imperative commit subjects.
- Keep tests and static analysis green after each commit.

## Next steps

After local checks pass, open a pull request with a short summary of what you changed and why.
