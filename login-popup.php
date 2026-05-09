<!-- Back To Top -->
    <div class="progress-wrap">
        <svg class="progress-circle svg-content" width="100%" height="100%" viewBox="-1 -1 102 102">
            <path d="M50,1 a49,49 0 0,1 0,98 a49,49 0 0,1 0,-98" />
        </svg>
        <svg aria-hidden="true" class="arrow" width="16px" height="16px" viewBox="0 0 448 512" xmlns="http://www.w3.org/2000/svg">
            <path
                d="M34.9 289.5l-22.2-22.2c-9.4-9.4-9.4-24.6 0-33.9L207 39c9.4-9.4 24.6-9.4 33.9 0l194.3 194.3c9.4 9.4 9.4 24.6 0 33.9L413 289.4c-9.5 9.5-25 9.3-34.3-.4L264 168.6V456c0 13.3-10.7 24-24 24h-32c-13.3 0-24-10.7-24-24V168.6L69.2 289.1c-9.3 9.8-24.8 10-34.3.4z">
            </path>
        </svg>
    </div>

<div class="modal login-modal fade" id="user-login" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <ul class="nav nav-tabs" id="myTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="home-tab" data-bs-toggle="tab" data-bs-target="#home"
                                type="button" role="tab" aria-controls="home" aria-selected="true">
                                Log In
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile"
                                type="button" role="tab" aria-controls="profile" aria-selected="false">Registration</button>
                        </li>
                    </ul>
                </div>
                <div class="modal-body">
                    <div class="tab-content" id="myTabContent">
                        <div class="tab-pane fade show active" id="home" role="tabpanel" aria-labelledby="home-tab">
                            <div class="login-registration-form">
                                <div class="form-title">
                                    <h3>Log In</h3>
                                </div>
                                <form>
                                    <div class="form-inner mb-35">
                                        <input type="text" placeholder="User name or Email *">
                                    </div>
                                    <div class="form-inner">
                                        <input id="password" type="password" placeholder="Password *">
                                        <i class="bi bi-eye-slash" id="togglePassword"></i>
                                    </div>
                                    <div class="form-remember-forget">
                                        <div class="remember">
                                            <input type="checkbox" class="custom-check-box" id="check1">
                                            <label for="check1">Remember me</label>
                                        </div>
                                        <a href="#" class="forget-pass hover-underline">Forget Password</a>
                                    </div>
                                    <button class="primary-btn" type="submit">
                                        Log In
                                    </button>
                                    <a href="#" class="member">Not a member yet?</a>
                                </form>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="profile" role="tabpanel" aria-labelledby="profile-tab">
                            <div class="login-registration-form">
                                <div class="form-title">
                                    <h3>Registration</h3>
                                </div>
                                <form>
                                    <div class="form-inner mb-25">
                                        <input type="text" placeholder="User Name *">
                                    </div>
                                    <div class="form-inner mb-25">
                                        <input type="email" placeholder="Email Here *">
                                    </div>
                                    <div class="form-inner mb-25">
                                        <input id="password2" type="password" placeholder="Password *">
                                        <i class="bi bi-eye-slash" id="togglePassword2"></i>
                                    </div>
                                    <div class="form-inner mb-35">
                                        <input id="password3" type="password" placeholder="Confirm Password *">
                                        <i class="bi bi-eye-slash" id="togglePassword3"></i>
                                    </div>
                                    <button class="primary-btn" type="submit">
                                        Registration
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>