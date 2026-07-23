# Security Policy

## Supported Versions

Security updates are provided for the latest stable major version.

Pre-release versions, including beta and release candidate versions, are intended for testing before stable release.

## Reporting a Vulnerability

If you discover a security vulnerability, please do not open a public issue.

Please report it through GitHub's private vulnerability reporting feature when available, or contact the maintainer through the repository support channels.

## Scope

This library validates headers generated through its public API to reduce header injection risks.

Low-level raw cURL options are treated as an escape hatch. Values passed through raw options are the caller's responsibility.
