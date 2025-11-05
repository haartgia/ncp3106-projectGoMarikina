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

				<!-- Swap order: Email first, then Mobile -->
				<label class="auth-field" for="signupEmail">
					<span class="auth-field-label">Email</span>
					<!-- Must include an @ and domain, no spaces -->
					<input type="email" id="signupEmail" name="email" placeholder="Email" required autocomplete="email" pattern="^[^\s@]+@[^\s@]+\.[^\s@]+$" />
				</label>

				<label class="auth-field" for="signupMobile">
					<span class="auth-field-label">Mobile Number</span>
					<!-- PH format: +63XXXXXXXXXX (10 digits after +63). No spaces allowed. -->
					<input type="tel" id="signupMobile" name="mobile" placeholder="+63XXXXXXXXXX" value="+63" required autocomplete="tel" pattern="\+63\d{10}" maxlength="13" inputmode="tel" />
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

				<label class="auth-field" for="signupConfirmPassword">
					<span class="auth-field-label">Confirm Password</span>
					<div class="auth-field-input">
						<input type="password" id="signupConfirmPassword" name="confirm_password" placeholder="Confirm Password" required autocomplete="new-password" data-password-field minlength="8" pattern="^(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9])(?!.*\s).{8,}$" title="Must match the password above" />
						<button type="button" class="auth-field-toggle" data-password-toggle="signupConfirmPassword" aria-label="Show password">
							<span class="auth-toggle-icon" aria-hidden="true"></span>
						</button>
					</div>
				</label>
				<p id="signupConfirmHint" class="auth-error" hidden>Passwords do not match.</p>

				<!-- Password feedback (meter + tooltip requirements) -->
				<div class="password-feedback">
					<!-- Password Strength Meter -->
					<div id="signupPasswordMeter" class="password-strength" aria-live="polite" aria-atomic="true" hidden>
						<div class="password-strength__bar" aria-hidden="true">
							<span class="password-strength__fill" data-strength-fill style="width:0%"></span>
						</div>
						<span class="password-strength__label" data-strength-label>Weak</span>
					</div>

					<!-- Password requirements tooltip (Sign Up) -->
					<div id="signupPasswordReqs" class="password-reqs password-tooltip" role="tooltip" aria-live="polite" aria-atomic="true" hidden>
						<p class="password-tooltip__title">Your password must have:</p>
						<ul>
							<li data-rule="length" class="invalid">At least 8 characters</li>
							<li data-rule="uppercase" class="invalid">At least 1 uppercase letter (A–Z)</li>
							<li data-rule="number" class="invalid">At least 1 number (0–9)</li>
							<li data-rule="special" class="invalid">At least 1 special character (!@#$…)</li>
							<li data-rule="spaces" class="invalid">No spaces</li>
						</ul>
					</div>
				</div>
				<p id="signupPasswordStrength" class="password-strong" hidden>Strong password</p>

				<button type="submit" class="auth-submit">Create Account</button>
			</form>

			<p class="auth-footer-copy auth-footer-login">Don't have an account? <a href="#" class="auth-link" data-auth-switch="signup">Sign up</a></p>
			<p class="auth-footer-copy auth-footer-signup" hidden>Already have an account? <a href="#" class="auth-link" data-auth-switch="login">Log in</a></p>
		</div>
	</section>

	<!-- Forgot Password Modal -->
	<div id="forgotPasswordModal" class="modal" hidden>
		<div class="modal-content forgot-modal-card" role="dialog" aria-modal="true" aria-labelledby="forgotPasswordTitle">
				

				<div style="margin-bottom: 12px;">
					<h2 id="forgotPasswordTitle" class="forgot-title">Reset Your Password</h2>
					<p class="forgot-desc">No worries! Enter your email address and we'll send you a link to reset your password.</p>
				</div>

				<button type="button" id="forgotModalClose" aria-label="Close" class="forgot-close">
					<svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" fill="none">
						<path d="M18 6 6 18M6 6l12 12"/>
					</svg>
				</button>

				<div id="forgotMessageContainer"></div>

				<form id="forgotPasswordForm">
					<label class="auth-field" for="forgotEmail" style="margin-bottom: 28px;">
						<span class="auth-field-label">Email Address</span>
						<input type="email" id="forgotEmail" name="email" placeholder="you@example.com" required autocomplete="email" />
					</label>

					<div class="forgot-actions" style="align-items: stretch;">
						<button type="button" id="forgotCancelBtn" class="btn-secondary">Cancel</button>
						<button type="submit" id="forgotSubmitBtn" class="auth-submit">Send Reset Link</button>
					</div>
				</form>

				<div class="forgot-footer">
					<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" stroke-linecap="round" fill="none" style="flex-shrink: 0;">
						<circle cx="12" cy="12" r="10"/>
						<path d="M12 16v-4m0-4h.01"/>
					</svg>
					<span>Remember your password? <a href="#" class="auth-link" onclick="document.getElementById('forgotModalClose').click(); return false;">Sign in</a></span>
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
			const isSuccess = type === 'success';
			const icon = isSuccess
				? '<svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" fill="none"><polyline points="20 6 9 17 4 12"/></svg>'
				: '<svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" fill="none"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>';
			messageContainer.innerHTML = `<div role=\"alert\" class=\"inline-alert ${isSuccess ? 'inline-alert--success' : 'inline-alert--error'}\"><span class=\"inline-alert__icon\">${icon}</span><span>${message}</span></div>`;
		}

		function clearMessage() {
			messageContainer.innerHTML = '';
		}

		function openModal() {
			clearMessage();
			form.reset();
			modal.removeAttribute('hidden');
			modal.setAttribute('open', '');
			document.body.classList.add('modal-open');
			document.getElementById('forgotEmail').focus();
		}

		function closeModal() {
			modal.removeAttribute('open');
			modal.setAttribute('hidden', '');
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

		// Client-side confirm password check on signup form
		const signupPwd = document.getElementById('signupPassword');
		const signupConfirm = document.getElementById('signupConfirmPassword');
        const signupConfirmHint = document.getElementById('signupConfirmHint');
		function validateConfirm() {
			if (!signupPwd || !signupConfirm) return;
			const mismatch = signupConfirm.value && signupConfirm.value !== signupPwd.value;
			signupConfirm.setCustomValidity(mismatch ? 'Passwords do not match' : '');
			if (signupConfirmHint) signupConfirmHint.hidden = !mismatch;
		}
		signupPwd?.addEventListener('input', validateConfirm);
		signupConfirm?.addEventListener('input', validateConfirm);

		// Live password requirements updater for Sign Up password field
		(function initPasswordRequirements(){
			const reqs = document.getElementById('signupPasswordReqs');
			const signupToggle = document.querySelector('[data-password-toggle="signupPassword"]');
			const strengthEl = document.getElementById('signupPasswordStrength');
			const meter = document.getElementById('signupPasswordMeter');
			const meterFill = meter ? meter.querySelector('[data-strength-fill]') : null;
			const meterLabel = meter ? meter.querySelector('[data-strength-label]') : null;
			if (!signupPwd || !reqs) return;

			const items = {
				length: reqs.querySelector('li[data-rule="length"]'),
				uppercase: reqs.querySelector('li[data-rule="uppercase"]'),
				number: reqs.querySelector('li[data-rule="number"]'),
				special: reqs.querySelector('li[data-rule="special"]'),
				spaces: reqs.querySelector('li[data-rule="spaces"]')
			};

			const compute = (v) => ({
				length: (v || '').length >= 8,
				uppercase: /[A-Z]/.test(v || ''),
				number: /\d/.test(v || ''),
				special: /[^A-Za-z0-9]/.test(v || ''),
				spaces: !/\s/.test(v || '')
			});

			const computeStrength = (v) => {
				const r = compute(v);
				let score = 0;
				['length','uppercase','number','special','spaces'].forEach(k => { if (r[k]) score++; });
				// Map score to level and width
				let level = 'weak';
				// Proportional fill with partial credit for length up to 8 chars
				const len = (v || '').length;
				const lengthFraction = Math.min(len, 8) / 8; // 0..1
				const progress = lengthFraction
					+ (r.uppercase ? 1 : 0)
					+ (r.number ? 1 : 0)
					+ (r.special ? 1 : 0)
					+ (r.spaces ? 1 : 0); // total 0..5
				let width = Math.round((progress / 5) * 100);
				// Strong only if ALL rules are satisfied
				if (score === 5) { level = 'strong'; width = 100; }
				else if (score >= 3) { level = 'moderate'; width = 60; }
				return { level, width, score };
			};

			const update = () => {
				const v = signupPwd.value || '';
				const r = compute(v);
				let allOk = true;
				Object.keys(items).forEach((key) => {
					const li = items[key];
					if (!li) return;
					const ok = !!r[key];
					li.classList.toggle('valid', ok);
					li.classList.toggle('invalid', !ok);
					// Show all rules in tooltip; just change states
					li.hidden = false;
					if (!ok) allOk = false;
				});
				// Show requirements panel only when the password field is focused; hide otherwise
				const isFocused = document.activeElement === signupPwd;
				reqs.hidden = !isFocused;

				// Update strength meter continuously
				if (meter && meterFill && meterLabel) {
					if (!isFocused) {
						// Hide meter when not focused
						meter.hidden = true;
						meter.removeAttribute('data-level');
						meterFill.style.width = '0%';
						meterLabel.textContent = 'Weak';
					} else {
						// Show meter on focus; if empty, show 0%
						if (!v) {
							meter.hidden = false;
							meter.removeAttribute('data-level');
							meterFill.style.width = '0%';
							meterLabel.textContent = 'Weak';
						} else {
							const m = computeStrength(v);
							meter.hidden = false;
							meter.setAttribute('data-level', m.level);
							meterFill.style.width = m.width + '%';
							meterLabel.textContent = m.level;
						}
					}
				}

				// Position tooltip to follow the bar fill; keep the arrow pointing to the fill end
				if (isFocused && reqs && meter) {
					const feedback = meter.closest('.password-feedback');
					const bar = meter.querySelector('.password-strength__bar');
					if (feedback && bar) {
						const wrapRect = feedback.getBoundingClientRect();
						const barRect = bar.getBoundingClientRect();
						const pct = (meterFill && meterFill.style.width) ? (parseInt(meterFill.style.width, 10) || 0) / 100 : 0;
						const ARROW_OFFSET = 6; // nudge arrow slightly past the rounded bar cap
						const desiredX = (barRect.left - wrapRect.left) + (barRect.width * pct) + ARROW_OFFSET; // in wrapper coords
						const tooltipWidth = reqs.offsetWidth || 300;
						// Prefer arrow offset 24px from tooltip left
						const preferredArrow = 24;
						let left = desiredX - preferredArrow;
						// Clamp tooltip within wrapper bounds
						left = Math.max(0, Math.min(left, wrapRect.width - tooltipWidth));
						// Compute arrow pos within tooltip so tip still points to desiredX
						let arrowLeft = desiredX - left;
						arrowLeft = Math.max(12, Math.min(arrowLeft, tooltipWidth - 24));
						reqs.style.left = left + 'px';
						reqs.style.setProperty('--arrow-left', arrowLeft + 'px');
					}
				}

				// Keep old text indicator hidden (we use the meter instead)
				if (strengthEl) strengthEl.hidden = true;
			};

			update();
			signupPwd.addEventListener('input', update);
			signupPwd.addEventListener('focus', update);
			signupPwd.addEventListener('blur', update);
		})();
	})();
	</script>
