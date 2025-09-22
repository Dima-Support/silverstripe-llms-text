# Silverstripe LLMs.txt Generator

Generate an `llms.txt` file in your project’s public webroot that lists **only** pages which are **Published** and have **Show in search** set to **Yes**. This file can be consumed by external systems (e.g. LLM indexers) to discover public-facing content on your site.

---

## What it does

* Scans your Silverstripe site for pages that:

  * are in the "Live" stage (published), and
  * have `ShowInSearch = true`.
* Writes one absolute URL per line to `public/llms.txt` (or your configured web root).
* Provides a build task you can run via `sake` or schedule via `cron`.

---

## Requirements

* Silverstripe 5.x or 6.0
* PHP version compatible with your Silverstripe version
* A project using the **public** webroot structure (default on SS5+). If your webroot differs, configure the path (see **Configuration** below).

---

## Installation

Install via Composer:

```bash
composer require dima-support/silverstripe-llms-txt
```

After installation, rebuild your project:

```bash
# Rebuild database, regenerate config, and clear caches
vendor/bin/sake dev/build flush=all

# (Optional but helpful) refresh autoloaders
composer dump-autoload
```

> If you deploy with an immutable filesystem (e.g., on Platform-as-a-Service), ensure your instance can write to the public webroot or use a writable alternative path (see **Configuration**).

---

## Usage

Run the provided build task to generate/update the file:

```bash
vendor/bin/sake dev/tasks/generate-llms-txt
```

This will:

* Query all `SiteTree` records that are Published and searchable
* Build a list of canonical absolute URLs
* Write them to `<public>/llms.txt`

You can re-run the task anytime—it's idempotent.

### Scheduling (cron)

Generate `llms.txt` on a schedule (e.g., every hour):

```cron
# command
0 * * * * cd /path/to/project && /usr/bin/env php vendor/bin/sake dev/tasks/generate-llms-txt > /dev/null 2>&1
```

Adjust frequency to suit your publishing cadence.

---

## Configuration

By default, the task writes to the detected public webroot (typically `public/llms.txt`). You can override the output path and other options in your YAML config:

```yaml
# app/_config/llms-txt.yml
Task\LLMsTxt\GenerateLLMsTxtTask:
  output_path: 'public/llms.txt'   # Relative to project root, or absolute path
  # base_url: 'https://example.com' # Force a base URL if not auto-detected
  # include_drafts: false           # Safety net, should remain false
  # additional_filters: []          # Add extra ORM filters if needed
```

> If your project uses a non-standard webroot, be sure `output_path` points to the actual public document root served by your web server.

---

## Output

* **Location:** `<public>/llms.txt`
* **Format:** One URL per line (LF line endings)
* **Example:**

```
https://example.com/
https://example.com/about-us/
https://example.com/blog/
https://example.com/blog/how-we-work/
```

---

## Inclusion rules

A page is included **only if all** of the following are true:

1. It exists on the **Live** stage (Published)
2. `ShowInSearch = true`
3. It resolves to a canonical absolute URL

### Excluding specific pages

You can proactively exclude pages by:

* Unchecking **Show in search** in the CMS for that page, **or**
* Adding custom filters via configuration (see `additional_filters`), **or**
* Extending the query with a project-specific extension (see below).

---

## Development notes

* The task uses standard Silverstripe ORM to query `SiteTree` with filters:

  * `ShowInSearch = 1`
  * Present on \*\*Li
