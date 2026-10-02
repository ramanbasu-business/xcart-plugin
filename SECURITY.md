# Security notes

This project is a legacy X-Cart PHP integration and should be treated as historical code rather than a modern secure-by-default application.

## What was cleaned up

- real admin credentials were removed
- local test URLs were redacted
- sample product image URLs were replaced with placeholders
- outdated client-specific metadata was removed from code comments and examples

## Remaining risks to be aware of

This repository contains legacy code patterns that were common in older storefront plugins:

- query-string credentials and authentication data
- direct SQL string construction in the queue layer
- older XML export endpoints without modern request validation
- data retention and logging behavior tied to a local plugin folder

These were not added as new features; they are part of the original legacy implementation and remain as known constraints.

## Guidance

Before any production reuse or public deployment:

1. move all credentials into the storefront's secure admin configuration model
2. validate access using the platform's current auth rules
3. remove or rewrite any query-string password flow
4. review job-processing and file logging for sensitive data exposure
5. validate the X-Cart store environment before enabling the module in production

## Disclosure

No live secrets or production endpoints are intentionally included in this repository.
