# Osmium Restock Leads service

Lets customers leave an email to hear when an out-of-stock product is back, collects those requests and lists them in
admin. Repo `Osmium-Services/osmium-restock-leads`, v0.2.2<!--v:osmium-restock-leads-->, category shop. Extracted 2026-10-10. See [[Core and Services]].

## What it does

- **Endpoint** `POST /api/restock-leads/`: fields `email`, `pid`, `token`, `zip` (honeypot). Same checks as Reviews
  (honeypot, one-use token, minimum submit time), plus whatever bot protection is installed, asked through the
  `form.verify` hook (form name `restock`) so the service never names a provider. Then a valid email and a positive product
  id. Every bot-check refusal says the same generic "technical problem" message (`RestockRequest::TECHNICAL_PROBLEM`). The
  visitor is sent back to the page they came from (path only) with a one-time `restockNotice` (sent or failed).
- **Product page data** (`product.page.data`): `restockNotifySet` (this visitor already asked about this product, this
  session) and `restockNotice`. The service sets **no cookie**: the request is remembered in the session, so there is no
  cookie-consent question.
- **Admin** (Shop > Restock Leads): a read-only list of leads with product, reference, email (a `mailto:` link) and date.
  Table `productrestockleads` (same name as the old core table).
- **Collect-only on purpose.** Nothing emails customers when stock returns. On SolarGo2 a few emails were collected on
  per-order products and the owner personally offered 5% off when stock arrived; an automatic email would have missed that.

## Core keeps

The product's `restock_at` date and the status's `restocking` flag.

## Theme side

The product page asks `form.widget` (form `restock`) and echoes the result inside the form before the Submit button, and
shows `restockNotice` in the shared `notice-modal.phtml`. The modal sits outside the form's `if`s: once a request is sent
the form is no longer drawn, so an inline notice would never be seen.

## Theme change from the old core form

Post `email`, `pid`, `token` and `zip` to `/api/restock-leads/` (no `type` field) and read `restockNotice` where the old
`stockNotifyError` flash was read. AllFast's `product-status-extra.phtml` still uses the old endpoint.

## Not tested

A themed product page; the endpoint, the session state and the admin list were checked directly.
