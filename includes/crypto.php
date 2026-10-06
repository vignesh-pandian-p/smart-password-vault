<?php
/**
 * Smart Password Vault - Cryptographic Utilities
 */

if (!defined('VAULT_CIPHER_KEY')) {
    define('VAULT_CIPHER_KEY', 'my_secret_encryption_key_123');
}

if (!defined('VAULT_CIPHER_IV')) {
    define('VAULT_CIPHER_IV', '1234567890123456');
}

/**
 * Encrypt a plain-text password using AES-256-CBC
 */
function encrypt_password($password) {
    return openssl_encrypt($password, "AES-256-CBC", VAULT_CIPHER_KEY, 0, VAULT_CIPHER_IV);
}

/**
 * Decrypt a base64-encoded ciphertext using AES-256-CBC
 */
function decrypt_password($encrypted) {
    $decoded = base64_decode($encrypted, true);
    if ($decoded === false) {
        return null;
    }
    $decrypted = openssl_decrypt($decoded, "AES-256-CBC", VAULT_CIPHER_KEY, OPENSSL_RAW_DATA, VAULT_CIPHER_IV);
    return ($decrypted !== false) ? $decrypted : null;
}
