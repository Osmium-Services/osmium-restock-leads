-- Open requests overall. Counts only, never an email.
SELECT COUNT(*) AS requests,
       COUNT(DISTINCT LOWER(TRIM(email))) AS customers,
       COUNT(DISTINCT product_id) AS products
FROM {TABLE}
WHERE deleted_at IS NULL
