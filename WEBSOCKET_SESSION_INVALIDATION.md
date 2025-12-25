# Real-Time Session Invalidation with WebSocket

## Overview
This feature provides instant, real-time logout when a user logs in from a different device. When Device B logs in, Device A is immediately disconnected without requiring any user interaction or page refresh.

## Technology Stack
- **Backend**: Laravel Broadcasting with Laravel Reverb
- **Frontend**: Laravel Echo with Pusher JS client
- **Protocol**: WebSocket (real-time bidirectional communication)

## How It Works

### 1. Backend Broadcasting
When a user logs in from a new device (Device B):

1. **Session Deletion** - `FortifyServiceProvider` deletes all other sessions for that user from the database
2. **Event Broadcasting** - A `SessionInvalidated` event is broadcast to the private channel `session.{userId}` via WebSocket
3. **Channel Authorization** - Laravel verifies the user owns the channel in `routes/channels.php`

```php
// app/Events/SessionInvalidated.php
class SessionInvalidated implements ShouldBroadcast
{
    public function __construct(
        public int $userId,
        public string $reason = 'session_invalidated'
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('session.' . $this->userId)];
    }
}
```

### 2. Frontend WebSocket Connection
When the app loads for an authenticated user:

1. **Echo Initialization** - Laravel Echo connects to Reverb WebSocket server with user's CSRF token
2. **Channel Subscription** - Subscribes to private channel `session.{userId}`
3. **Event Listener** - Waits for `session.invalidated` event

```typescript
// resources/js/hooks/use-session-invalidation.ts
export function useSessionInvalidation(userId: number | null | undefined) {
    useEffect(() => {
        if (!userId) return;

        const channel = echo.private(`session.${userId}`);
        
        channel.listen('.session.invalidated', (event: any) => {
            // Force redirect to login
            window.location.href = '/login';
        });

        return () => {
            channel.stopListening('.session.invalidated');
            echo.leave(`session.${userId}`);
        };
    }, [userId]);
}
```

### 3. Auto-Logout Flow
When Device B logs in:
1. Backend deletes Device A's session from database
2. Backend broadcasts `SessionInvalidated` event via WebSocket
3. Device A receives event instantly (no polling, no delay)
4. Device A automatically redirects to `/login`
5. User sees: "Anda telah login dari perangkat lain. Silakan login kembali."

## Configuration

### Environment Variables (.env)
```env
BROADCAST_CONNECTION=reverb

REVERB_APP_ID=264329
REVERB_APP_KEY=zrbyocslhpi3v1zpsp1q
REVERB_APP_SECRET=jgqm7iymxhnx32f0it5y
REVERB_HOST="localhost"
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

### Broadcasting Config
File: `config/broadcasting.php`
```php
'default' => env('BROADCAST_CONNECTION', 'reverb'),

'connections' => [
    'reverb' => [
        'driver' => 'reverb',
        'key' => env('REVERB_APP_KEY'),
        'secret' => env('REVERB_APP_SECRET'),
        'app_id' => env('REVERB_APP_ID'),
        'options' => [
            'host' => env('REVERB_HOST'),
            'port' => env('REVERB_PORT', 8080),
            'scheme' => env('REVERB_SCHEME', 'http'),
        ],
    ],
],
```

### Channel Authorization
File: `routes/channels.php`
```php
Broadcast::channel('session.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});
```

## Starting the WebSocket Server

### Development
Start Reverb server locally:
```bash
php artisan reverb:start
```

The server will run on `ws://localhost:8080` by default.

### Production (Hosting)
You'll need to:
1. Update `.env` with production Reverb settings
2. Use supervisor or systemd to keep Reverb running
3. Configure nginx/Apache to proxy WebSocket connections
4. Update REVERB_HOST to your domain
5. Set REVERB_SCHEME to `https` and REVERB_PORT to `443`

Example supervisor config:
```ini
[program:reverb]
command=php /path/to/artisan reverb:start
directory=/path/to/application
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/log/reverb.log
```

## Testing

### Manual Test (2 Browsers)
1. **Device A**: Login dengan Chrome → Navigate ke dashboard
2. **Device B**: Login dengan Firefox → Masukkan 2FA code
3. **Expected**: Device A langsung redirect ke `/login` tanpa refresh manual

### Check WebSocket Connection
Open browser DevTools → Network tab → Filter "WS" (WebSocket):
- Should see connection to `ws://localhost:8080/app/zrbyocslhpi3v1zpsp1q`
- Connection should show "101 Switching Protocols"
- Messages tab should show subscription to `private-session.{userId}`

### Debugging
```bash
# Check if Reverb is running
php artisan reverb:start --debug

# Check Laravel logs
tail -f storage/logs/laravel.log

# Check browser console
# Should see: "Session invalidated: {userId: 1, reason: 'session_invalidated'}"
```

## Key Files Modified

### Backend
- `app/Events/SessionInvalidated.php` - Broadcast event definition
- `app/Providers/FortifyServiceProvider.php` - Triggers broadcast on login
- `routes/channels.php` - Channel authorization
- `config/broadcasting.php` - Reverb configuration

### Frontend
- `resources/js/lib/echo.ts` - Laravel Echo initialization
- `resources/js/hooks/use-session-invalidation.ts` - WebSocket listener hook
- `resources/js/layouts/app/app-sidebar-layout.tsx` - Hook integration
- `resources/js/types/global.d.ts` - Pusher TypeScript types

## Benefits Over Polling
- ✅ **Instant**: No delay, logout happens immediately
- ✅ **Efficient**: No repeated HTTP requests every N seconds
- ✅ **Scalable**: WebSocket connections are lightweight
- ✅ **Battery Friendly**: Persistent connection uses less power than polling
- ✅ **Real-time**: True push notification, not pull-based

## Maintenance

### Monitor WebSocket Connections
```bash
# Check active connections
php artisan reverb:connections

# Restart server
php artisan reverb:restart
```

### Update Reverb Keys
If you change REVERB_APP_KEY:
1. Update `.env` file
2. Restart Reverb server: `php artisan reverb:restart`
3. Rebuild frontend: `npm run build` (updates VITE_REVERB_APP_KEY)

## Troubleshooting

### WebSocket Connection Failed
**Problem**: Frontend shows "WebSocket connection to 'ws://localhost:8080' failed"

**Solution**:
1. Check if Reverb is running: `php artisan reverb:start`
2. Verify REVERB_PORT is correct in `.env`
3. Check firewall isn't blocking port 8080

### Event Not Received
**Problem**: Device A doesn't logout when Device B logs in

**Solution**:
1. Check browser console for connection errors
2. Verify channel authorization in `routes/channels.php`
3. Check Laravel logs for broadcast errors
4. Ensure BROADCAST_CONNECTION=reverb in `.env`

### CORS Issues
**Problem**: "Access to XMLHttpRequest has been blocked by CORS policy"

**Solution**:
Update `config/broadcasting.php`:
```php
'reverb' => [
    'options' => [
        // ...existing options
        'allowed_origins' => ['http://localhost:5173', env('APP_URL')],
    ],
],
```

## Security Considerations
- Private channels require authentication
- CSRF token included in all Echo requests
- Channel authorization prevents unauthorized subscriptions
- Only broadcasts to other devices (using `->toOthers()`)
- Session tokens stored securely in database
