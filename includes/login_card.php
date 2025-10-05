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
					<input type="text" id="loginEmail" name="email" placeholder="Email" required autocomplete="username" />
				</label>

				<label class="auth-field" for="loginPassword">
					<span class="auth-field-label">Password</span>
					<div class="auth-field-input">
						<input type="password" id="loginPassword" name="password" placeholder="Password" required autocomplete="current-password" data-password-field />
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

			<form class="auth-form auth-form-signup" action="#" method="post" novalidate hidden>
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
					<input type="tel" id="signupMobile" name="mobile" placeholder="Mobile Number" required autocomplete="tel" />
				</label>

				<label class="auth-field" for="signupEmail">
					<span class="auth-field-label">Email</span>
					<input type="email" id="signupEmail" name="email" placeholder="Email" required autocomplete="email" />
				</label>

				<label class="auth-field" for="signupPassword">
					<span class="auth-field-label">Password</span>
					<div class="auth-field-input">
						<input type="password" id="signupPassword" name="password" placeholder="Password" required autocomplete="new-password" data-password-field />
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
