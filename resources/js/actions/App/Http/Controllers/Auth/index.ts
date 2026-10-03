import AuthenticatedSessionController from './AuthenticatedSessionController';
import RegisteredUserController from './RegisteredUserController';
import SsoOAuthController from './SsoOAuthController';
const Auth = {
    AuthenticatedSessionController: Object.assign(
        AuthenticatedSessionController,
        AuthenticatedSessionController,
    ),
    SsoOAuthController: Object.assign(SsoOAuthController, SsoOAuthController),
    RegisteredUserController: Object.assign(
        RegisteredUserController,
        RegisteredUserController,
    ),
};

export default Auth;
