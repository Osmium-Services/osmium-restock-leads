SELECT rl.id, rl.product_id, rl.email, rl.created_at, rl.updated_at,
       p.model_name, p.reference
FROM {TABLE} rl
LEFT JOIN {PREFIX}products p ON rl.product_id = p.id
WHERE rl.deleted_at IS NULL
ORDER BY rl.created_at DESC
