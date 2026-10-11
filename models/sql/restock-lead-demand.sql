-- Open requests grouped by product, with the product's current state. Counts and dates only, never an email.
SELECT rl.product_id,
       P.model_name, P.reference, P.restock_at, P.deleted_at AS product_deleted_at,
       PB.name AS brand_name,
       PS.product_available AS available, PS.restocking AS restocking,
       COUNT(*) AS requests,
       COUNT(DISTINCT LOWER(TRIM(rl.email))) AS customers,
       MIN(rl.created_at) AS first_request,
       MAX(rl.created_at) AS last_request
FROM {TABLE} rl
LEFT JOIN {PREFIX}products P ON P.id = rl.product_id
LEFT JOIN {PREFIX}productbrands PB ON PB.id = P.brand_id
LEFT JOIN {PREFIX}productstatus PS ON PS.id = P.status_id
WHERE rl.deleted_at IS NULL
GROUP BY rl.product_id, P.model_name, P.reference, P.restock_at, P.deleted_at, PB.name, PS.product_available, PS.restocking
ORDER BY customers DESC, last_request DESC
