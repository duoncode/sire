---
title: Installation
---

# Installation

This guide shows how to install Sire in an application and how to set up a local checkout for library development.

## Requirements

Before you install Sire, make sure your environment matches the supported runtime and tooling versions.

- PHP `^8.5`
- Composer

## Install in an application

Use Composer to add Sire as a project dependency.

1. Run the dependency install command.

    ```bash
    composer require duon/sire
    ```

2. Ensure your Composer autoloader is loaded in your application bootstrap.

3. Create a shape and run a first validation call.

## Install for local development

If you want to contribute to Sire itself, install the repository dependencies including development tools.

1. Clone the repository.
2. Install dependencies.

    ```bash
    composer install
    ```

3. Run the baseline quality checks.

    ```bash
    composer test
    composer types
    ```

## Next steps

Continue with the [usage guide](usage.md) to build your first real shape.
