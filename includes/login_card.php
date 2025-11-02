<?php
require_once __DIR__ . '/../config/auth.php';

if (is_logged_in()) {
	return;
}

$redirectTarget = $redirectTarget ?? ($_SERVER['REQUEST_URI'] ?? 'profile.php');
$loginError = $_SESSION['login_error'] ?? null;
unset($_SESSION['login_error']);
?>
	<section class="auth-content" aria-labelledby="auth-card-title">
		<div class="auth-card" data-auth-mode="login" role="form">
			<header class="auth-card-header">
				<h2 id="auth-card-title" class="auth-card-title" data-auth-title>Log In</h2>
				<?php if ($loginError): ?>
					<p class="auth-error" role="alert"><?php echo htmlspecialchars($loginError); ?></p>
				<?php endif; ?>
			</header>

			<form class="auth-form auth-form-login" action="login.php" method="post" novalidate data-auth-server="true">
				<input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirectTarget); ?>">

				<label class="auth-field" for="loginEmail">
					<span class="auth-field-label">Email</span>
					<input type="text" id="loginEmail" name="email" placeholder="Email" required autocomplete="username" pattern="^[^\s@]+@[^\s@]+\.[^\s@]+$" />
				</label>

				<label class="auth-field" for="loginPassword">
					<span class="auth-field-label">Password</span>
					<div class="auth-field-input">
						<input type="password" id="loginPassword" name="password" placeholder="Password" required autocomplete="current-password" data-password-field pattern="^\S+$" title="No spaces allowed" />
						<button type="button" class="auth-field-toggle" data-password-toggle="loginPassword" aria-label="Show password">
							<span class="auth-toggle-icon" aria-hidden="true"></span>
						</button>
					</div>
				</label>

				<div class="auth-links">
					<a href="#" class="auth-link" data-auth-action="forgot">Forgot Password?</a>
				</div>

				<button type="submit" class="auth-submit">Sign In</button>
			</form>

			<form class="auth-form auth-form-signup" action="register.php" method="post" novalidate hidden>
				<div class="auth-field-row">
					<label class="auth-field" for="signupFirstName">
						<span class="auth-field-label">First Name</span>
						<input type="text" id="signupFirstName" name="first_name" placeholder="First Name" required autocomplete="given-name" />
					</label>
					<label class="auth-field" for="signupLastName">
						<span class="auth-field-label">Last Name</span>
						<input type="text" id="signupLastName" name="last_name" placeholder="Last Name" required autocomplete="family-name" />
					</label>
				</div>

				<label class="auth-field" for="signupMobile">
					<span class="auth-field-label">Mobile Number</span>
					<!-- PH format: +63XXXXXXXXXX (10 digits after +63). No spaces allowed. -->
					<input type="tel" id="signupMobile" name="mobile" placeholder="+63XXXXXXXXXX" value="+63" required autocomplete="tel" pattern="\+63\d{10}" maxlength="13" inputmode="tel" />
				</label>

				<label class="auth-field" for="signupEmail">
					<span class="auth-field-label">Email</span>
					<!-- Must include an @ and domain, no spaces -->
					<input type="email" id="signupEmail" name="email" placeholder="Email" required autocomplete="email" pattern="^[^\s@]+@[^\s@]+\.[^\s@]+$" />
				</label>

				<label class="auth-field" for="signupPassword">
					<span class="auth-field-label">Password</span>
					<div class="auth-field-input">
						<!-- Require: at least 8 chars, 1 uppercase, 1 number, 1 special char, and no spaces -->
						<input type="password" id="signupPassword" name="password" placeholder="Password" required autocomplete="new-password" data-password-field minlength="8" pattern="^(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9])(?!.*\s).{8,}$" title="At least 8 characters, include an uppercase letter, a number, a special character, and no spaces" />
						<button type="button" class="auth-field-toggle" data-password-toggle="signupPassword" aria-label="Show password">
							<span class="auth-toggle-icon" aria-hidden="true"></span>
						</button>
					</div>
				</label>

				<button type="submit" class="auth-submit">Create Account</button>
			</form>

			<p class="auth-footer-copy auth-footer-login">Don't have an account? <a href="#" class="auth-link" data-auth-switch="signup">Sign up</a></p>
			<p class="auth-footer-copy auth-footer-signup" hidden>Already have an account? <a href="#" class="auth-link" data-auth-switch="login">Log in</a></p>
		</div>
	</section>

	<!-- Forgot Password Modal -->
	<div id="forgotPasswordModal" class="modal" hidden>
		<div class="modal-content" role="dialog" aria-modal="true" aria-labelledby="forgotPasswordTitle" style="max-width: 480px; border-radius: 16px; padding: 0;">
			<div style="padding: 32px;">
				<div style="display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 24px;">
					<div>
						<h2 id="forgotPasswordTitle" style="font-size: 1.5rem; font-weight: 700; color: #1e293b; margin: 0 0 8px 0;">Forgot Password?</h2>
						<p style="font-size: 0.95rem; color: #64748b; margin: 0;">Enter your email to receive a password reset link.</p>
					</div>
					<button type="button" class="modal-close" id="forgotModalClose" aria-label="Close" style="margin: -8px -8px 0 0;">
						<svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" stroke-linecap="round" fill="none">
							<path d="M18 6 6 18M6 6l12 12"/>
						</svg>
					</button>
				</div>

				<div id="forgotMessageContainer"></div>

				<form id="forgotPasswordForm">
					<label class="auth-field" for="forgotEmail" style="margin-bottom: 24px;">
						<span class="auth-field-label">Email Address</span>
						<input type="email" id="forgotEmail" name="email" placeholder="your.email@example.com" required autocomplete="email" style="padding: 12px 16px; font-size: 1rem;" />
					</label>

					<div style="display: flex; gap: 12px;">
						<button type="button" id="forgotCancelBtn" style="flex: 1; padding: 12px; background: #e2e8f0; color: #475569; border: none; border-radius: 8px; font-size: 0.95rem; font-weight: 600; cursor: pointer;">Cancel</button>
						<button type="submit" id="forgotSubmitBtn" class="auth-submit" style="flex: 1; margin: 0;">Send Reset Link</button>
					</div>
				</form>
			</div>
		</div>
	</div>

	<script>
	(function() {
		const modal = document.getElementById('forgotPasswordModal');
		const form = document.getElementById('forgotPasswordForm');
		const messageContainer = document.getElementById('forgotMessageContainer');
		const closeBtn = document.getElementById('forgotModalClose');
		const cancelBtn = document.getElementById('forgotCancelBtn');
		const submitBtn = document.getElementById('forgotSubmitBtn');
		const forgotLink = document.querySelector('[data-auth-action="forgot"]');

		function showMessage(message, type = 'error') {
			const className = type === 'success' ? 'auth-success' : 'auth-error';
			messageContainer.innerHTML = `<p class="${className}" role="alert" style="padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem;">${message}</p>`;
		}

		function clearMessage() {
			messageContainer.innerHTML = '';
		}

		function openModal() {
			clearMessage();
			form.reset();
			modal.hidden = false;
			document.body.classList.add('modal-open');
			document.getElementById('forgotEmail').focus();
		}

		function closeModal() {
			modal.hidden = true;
			document.body.classList.remove('modal-open');
			clearMessage();
		}

		if (forgotLink) {
			forgotLink.addEventListener('click', (e) => {
				e.preventDefault();
				openModal();
			});
		}

		if (closeBtn) closeBtn.addEventListener('click', closeModal);
		if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
		
		modal?.addEventListener('click', (e) => {
			if (e.target === modal) closeModal();
		});

		form?.addEventListener('submit', async (e) => {
			e.preventDefault();
			clearMessage();
			
			const email = document.getElementById('forgotEmail').value.trim();
			if (!email) {
				showMessage('Please enter your email address.');
				return;
			}

			submitBtn.disabled = true;
			submitBtn.textContent = 'Sending...';

			try {
				const formData = new FormData();
				formData.append('email', email);

				const response = await fetch('forgot-password.php', {
					method: 'POST',
					body: formData
				});

				const data = await response.json();

				if (data.success) {
					showMessage(data.message, 'success');
					form.reset();
					
					// Show demo link in console for development
					if (data.demo_link) {
						console.log('🔐 Password Reset Link (Demo):', data.demo_link);
						showMessage(data.message + ' Check browser console for the reset link (demo mode).', 'success');
					}

					// Close modal after 5 seconds
					setTimeout(() => {
						closeModal();
					}, 5000);
				} else {
					showMessage(data.message || 'Failed to send reset link. Please try again.');
				}
			} catch (error) {
				console.error('Forgot password error:', error);
				showMessage('An error occurred. Please try again.');
			} finally {
				submitBtn.disabled = false;
				submitBtn.textContent = 'Send Reset Link';
			}
		});
	})();
	</script>
