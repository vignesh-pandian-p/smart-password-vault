/**
 * Smart Password Vault - Password Generator & Strength Calculator
 */

const PasswordGenerator = {
    CHAR_UPPER: 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
    CHAR_LOWER: 'abcdefghijklmnopqrstuvwxyz',
    CHAR_NUMBERS: '0123456789',
    CHAR_SYMBOLS: '!@#$%^&*()_+-=[]{}|;:,.<>?',

    generate(options = {}) {
        const {
            length = 16,
            uppercase = true,
            lowercase = true,
            numbers = true,
            symbols = true,
        } = options;

        let charPool = '';
        const guaranteed = [];

        if (uppercase) {
            charPool += this.CHAR_UPPER;
            guaranteed.push(this.getRandomChar(this.CHAR_UPPER));
        }
        if (lowercase) {
            charPool += this.CHAR_LOWER;
            guaranteed.push(this.getRandomChar(this.CHAR_LOWER));
        }
        if (numbers) {
            charPool += this.CHAR_NUMBERS;
            guaranteed.push(this.getRandomChar(this.CHAR_NUMBERS));
        }
        if (symbols) {
            charPool += this.CHAR_SYMBOLS;
            guaranteed.push(this.getRandomChar(this.CHAR_SYMBOLS));
        }

        if (charPool === '') {
            charPool = this.CHAR_LOWER;
            guaranteed.push(this.getRandomChar(this.CHAR_LOWER));
        }

        const remainingLength = Math.max(0, length - guaranteed.length);
        const randomChars = [];

        // Cryptographically secure random bytes
        const randomValues = new Uint32Array(remainingLength);
        window.crypto.getRandomValues(randomValues);

        for (let i = 0; i < remainingLength; i++) {
            randomChars.push(charPool[randomValues[i] % charPool.length]);
        }

        const combined = [...guaranteed, ...randomChars];
        // Secure shuffle (Fisher-Yates)
        for (let i = combined.length - 1; i > 0; i--) {
            const jBuffer = new Uint32Array(1);
            window.crypto.getRandomValues(jBuffer);
            const j = jBuffer[0] % (i + 1);
            [combined[i], combined[j]] = [combined[j], combined[i]];
        }

        return combined.join('');
    },

    getRandomChar(charset) {
        const buf = new Uint32Array(1);
        window.crypto.getRandomValues(buf);
        return charset[buf[0] % charset.length];
    },

    evaluateStrength(password) {
        if (!password) {
            return { score: 0, percent: 0, label: 'Empty', colorClass: 'bg-gray-300' };
        }

        let score = 0;
        const len = password.length;

        // Length evaluation
        if (len >= 8) score += 1;
        if (len >= 12) score += 2;
        if (len >= 16) score += 2;

        // Variety evaluation
        const hasLower = /[a-z]/.test(password);
        const hasUpper = /[A-Z]/.test(password);
        const hasNumber = /[0-9]/.test(password);
        const hasSymbol = /[^a-zA-Z0-9]/.test(password);

        const typesCount = [hasLower, hasUpper, hasNumber, hasSymbol].filter(Boolean).length;
        score += typesCount;

        if (typesCount >= 4 && len >= 12) score += 1;

        // Scale to 0-4
        let label = 'Weak';
        let percent = 25;
        let colorClass = 'bg-red-500';

        if (score <= 2) {
            label = 'Weak';
            percent = 25;
            colorClass = 'bg-red-500';
        } else if (score <= 4) {
            label = 'Fair';
            percent = 50;
            colorClass = 'bg-yellow-500';
        } else if (score <= 6) {
            label = 'Good';
            percent = 75;
            colorClass = 'bg-blue-500';
        } else {
            label = 'Strong';
            percent = 100;
            colorClass = 'bg-emerald-500';
        }

        return { score, percent, label, colorClass };
    }
};

window.PasswordGenerator = PasswordGenerator;
