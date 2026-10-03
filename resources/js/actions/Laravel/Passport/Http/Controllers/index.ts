import AccessTokenController from './AccessTokenController';
import ApproveAuthorizationController from './ApproveAuthorizationController';
import ApproveDeviceAuthorizationController from './ApproveDeviceAuthorizationController';
import AuthorizationController from './AuthorizationController';
import DenyAuthorizationController from './DenyAuthorizationController';
import DenyDeviceAuthorizationController from './DenyDeviceAuthorizationController';
import DeviceAuthorizationController from './DeviceAuthorizationController';
import DeviceCodeController from './DeviceCodeController';
import DeviceUserCodeController from './DeviceUserCodeController';
import TransientTokenController from './TransientTokenController';
const Controllers = {
    AccessTokenController: Object.assign(
        AccessTokenController,
        AccessTokenController,
    ),
    AuthorizationController: Object.assign(
        AuthorizationController,
        AuthorizationController,
    ),
    DeviceUserCodeController: Object.assign(
        DeviceUserCodeController,
        DeviceUserCodeController,
    ),
    DeviceCodeController: Object.assign(
        DeviceCodeController,
        DeviceCodeController,
    ),
    TransientTokenController: Object.assign(
        TransientTokenController,
        TransientTokenController,
    ),
    ApproveAuthorizationController: Object.assign(
        ApproveAuthorizationController,
        ApproveAuthorizationController,
    ),
    DenyAuthorizationController: Object.assign(
        DenyAuthorizationController,
        DenyAuthorizationController,
    ),
    DeviceAuthorizationController: Object.assign(
        DeviceAuthorizationController,
        DeviceAuthorizationController,
    ),
    ApproveDeviceAuthorizationController: Object.assign(
        ApproveDeviceAuthorizationController,
        ApproveDeviceAuthorizationController,
    ),
    DenyDeviceAuthorizationController: Object.assign(
        DenyDeviceAuthorizationController,
        DenyDeviceAuthorizationController,
    ),
};

export default Controllers;
