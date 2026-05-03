---
title: Introduction
---

# Sire Validation Library

Sire is a PHP validation library that lets you define shapes with a compact rule DSL, validate arbitrary input, and consume a typed validation result.

> **Note:** This is a preview feature currently under active development.

## Documentation sections

Start with the section that matches your current task. Each section focuses on one workflow so you can move from install to production usage quickly.

- [Installation](installation.md)
- [Usage](usage.md)
- [Development](development.md)

## Core concepts

Sire uses a shape object that defines fields, field types, and validators. A validation run returns a `Result` object with typed violations, structured error output, and both coerced and pristine values.

- Define fields with `Shape::add()`.
- Describe constraints with the string DSL, for example `required` or `min:10`.
- Normalize present field input with `Rule::prepare()` before coercion and validation when needed.
- Add cross-field or post-validation checks with `Shape::review()` callbacks.
- Compose reusable custom shapes through `Contract\Shape`.
- Call `Shape::validate()` to get a `Result`.
- Read `isValid()`, `violations()`, `errors()`, `values()`, and `pristineValues()` on the result.

## Next steps

If you are new to the library, continue with the installation guide first.
