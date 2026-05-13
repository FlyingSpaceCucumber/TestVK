# Document Library
Temporary access system for documents

Security Features
Seed-Based Hashing (HMAC-SHA256): Every access link is generated using a unique, database-stored "seed." This ensures links can be instantly revoked and protects against URL manipulation or predictable token generation.

## Dynamic Validation 
Tokens are recalculated on-the-fly and compared using hash_equals() to prevent timing attacks.

## WP Nonces
All AJAX actions and administrative requests are protected by WordPress Nonces to prevent CSRF attacks.

## Strict Access 
Control Links are only valid for 1 hour and can only be generated for published documents by authorized administrators.
