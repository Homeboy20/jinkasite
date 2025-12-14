/**
 * Firebase Authentication Handler
 * Handles email/phone verification and OTP login
 */

// Initialize Firebase (config will be loaded inline)
let auth = null;
let recaptchaVerifier = null;

function initializeFirebase(config) {
    if (typeof firebase === 'undefined') {
        console.error('Firebase SDK not loaded');
        return;
    }
    
    try {
        if (!firebase.apps.length) {
            firebase.initializeApp(config);
        }
        auth = firebase.auth();
        console.log('Firebase initialized successfully');
    } catch (error) {
        console.error('Firebase initialization error:', error);
    }
}

// Initialize reCAPTCHA for phone authentication
function initRecaptcha(elementId = 'recaptcha-container') {
    if (!auth) {
        console.error('Firebase auth not initialized');
        return;
    }
    
    recaptchaVerifier = new firebase.auth.RecaptchaVerifier(elementId, {
        'size': 'invisible',
        'callback': (response) => {
            console.log('reCAPTCHA solved');
        },
        'expired-callback': () => {
            console.log('reCAPTCHA expired');
        }
    });
}

// Send email verification
async function sendEmailVerification(email) {
    if (!auth) return { success: false, message: 'Firebase not initialized' };
    
    try {
        const actionCodeSettings = {
            url: window.location.origin + '/verify-email.php',
            handleCodeInApp: true
        };
        
        await auth.sendSignInLinkToEmail(email, actionCodeSettings);
        window.localStorage.setItem('emailForSignIn', email);
        
        return {
            success: true,
            message: 'Verification email sent! Please check your inbox.'
        };
    } catch (error) {
        console.error('Email verification error:', error);
        return {
            success: false,
            message: getFirebaseErrorMessage(error.code)
        };
    }
}

// Send OTP to phone number
async function sendPhoneOTP(phoneNumber) {
    if (!auth) return { success: false, message: 'Firebase not initialized' };
    
    try {
        // Ensure phone number has country code
        if (!phoneNumber.startsWith('+')) {
            phoneNumber = '+' + phoneNumber;
        }
        
        if (!recaptchaVerifier) {
            initRecaptcha();
            if (recaptchaVerifier && recaptchaVerifier.render) {
                await recaptchaVerifier.render();
            }
        } else {
            // Re-verify token in case the previous one expired
            try {
                if (recaptchaVerifier.verify) {
                    await recaptchaVerifier.verify();
                }
            } catch (e) {
                recaptchaVerifier.clear();
                recaptchaVerifier = null;
                initRecaptcha();
                if (recaptchaVerifier && recaptchaVerifier.render) {
                    await recaptchaVerifier.render();
                }
            }
        }
        
        const confirmationResult = await auth.signInWithPhoneNumber(phoneNumber, recaptchaVerifier);
        window.confirmationResult = confirmationResult;
        
        return {
            success: true,
            message: 'OTP sent successfully! Check your phone.',
            confirmationResult: confirmationResult
        };
    } catch (error) {
        console.error('Phone OTP error:', error);
        if (recaptchaVerifier) {
            recaptchaVerifier.clear();
            recaptchaVerifier = null;
        }
        return {
            success: false,
            message: getFirebaseErrorMessage(error.code)
        };
    }
}

// Verify phone OTP
async function verifyPhoneOTP(otp) {
    if (!window.confirmationResult) {
        return { success: false, message: 'Please request OTP first' };
    }
    
    try {
        const result = await window.confirmationResult.confirm(otp);
        const user = result.user;
        
        // Get Firebase ID token
        const idToken = await user.getIdToken();
        
        return {
            success: true,
            message: 'Phone verified successfully!',
            user: {
                uid: user.uid,
                phoneNumber: user.phoneNumber,
                idToken: idToken
            }
        };
    } catch (error) {
        console.error('OTP verification error:', error);
        return {
            success: false,
            message: getFirebaseErrorMessage(error.code)
        };
    }
}

// Verify email link
async function verifyEmailLink(email = null) {
    if (!auth) return { success: false, message: 'Firebase not initialized' };
    
    if (auth.isSignInWithEmailLink(window.location.href)) {
        let emailToVerify = email || window.localStorage.getItem('emailForSignIn');
        
        if (!emailToVerify) {
            emailToVerify = window.prompt('Please provide your email for confirmation');
        }
        
        try {
            const result = await auth.signInWithEmailLink(emailToVerify, window.location.href);
            window.localStorage.removeItem('emailForSignIn');
            
            // Get Firebase ID token
            const idToken = await result.user.getIdToken();
            
            return {
                success: true,
                message: 'Email verified successfully!',
                user: {
                    uid: result.user.uid,
                    email: result.user.email,
                    idToken: idToken
                }
            };
        } catch (error) {
            console.error('Email link verification error:', error);
            return {
                success: false,
                message: getFirebaseErrorMessage(error.code)
            };
        }
    }
    
    return { success: false, message: 'Invalid verification link' };
}

// Create user with email and password (with email verification)
async function createUserWithEmail(email, password) {
    if (!auth) return { success: false, message: 'Firebase not initialized' };
    
    try {
        const userCredential = await auth.createUserWithEmailAndPassword(email, password);
        await userCredential.user.sendEmailVerification();
        
        const idToken = await userCredential.user.getIdToken();
        
        return {
            success: true,
            message: 'Account created! Please verify your email.',
            user: {
                uid: userCredential.user.uid,
                email: userCredential.user.email,
                emailVerified: userCredential.user.emailVerified,
                idToken: idToken
            }
        };
    } catch (error) {
        console.error('Email signup error:', error);
        return {
            success: false,
            message: getFirebaseErrorMessage(error.code)
        };
    }
}

// Sign in with email and password
async function signInWithEmail(email, password) {
    if (!auth) return { success: false, message: 'Firebase not initialized' };
    
    try {
        const userCredential = await auth.signInWithEmailAndPassword(email, password);
        const idToken = await userCredential.user.getIdToken();
        
        return {
            success: true,
            message: 'Login successful!',
            user: {
                uid: userCredential.user.uid,
                email: userCredential.user.email,
                emailVerified: userCredential.user.emailVerified,
                idToken: idToken
            }
        };
    } catch (error) {
        console.error('Email login error:', error);
        return {
            success: false,
            message: getFirebaseErrorMessage(error.code)
        };
    }
}

// Get human-readable error messages
function getFirebaseErrorMessage(errorCode) {
    const errorMessages = {
        'auth/invalid-phone-number': 'Invalid phone number format',
        'auth/missing-phone-number': 'Please enter a phone number',
        'auth/invalid-verification-code': 'Invalid OTP code',
        'auth/code-expired': 'OTP code has expired',
        'auth/too-many-requests': 'Too many attempts. Please try again later',
        'auth/email-already-in-use': 'Email already registered',
        'auth/invalid-email': 'Invalid email address',
        'auth/weak-password': 'Password should be at least 6 characters',
        'auth/user-not-found': 'No account found with this email',
        'auth/wrong-password': 'Incorrect password',
        'auth/network-request-failed': 'Network error. Please check your connection',
        'auth/quota-exceeded': 'SMS quota exceeded. Please try again later'
    };
    
    return errorMessages[errorCode] || 'An error occurred. Please try again.';
}

// Sign out
async function signOutFirebase() {
    if (!auth) return;
    try {
        await auth.signOut();
        console.log('Signed out successfully');
    } catch (error) {
        console.error('Sign out error:', error);
    }
}
