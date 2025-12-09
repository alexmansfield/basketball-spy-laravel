# Authentication API

Basketball Spy uses Laravel Sanctum for token-based API authentication. This guide covers all authentication endpoints.

## Base URL

```
https://your-domain.com/api
```

## Authentication Flow

### 1. Register New User

**Endpoint:** `POST /api/register`

**Request:**
```json
{
  "name": "John Scout",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "organization_id": 1
}
```

**Response:** `201 Created`
```json
{
  "user": {
    "id": 5,
    "name": "John Scout",
    "email": "john@example.com",
    "role": "scout",
    "organization": {
      "id": 1,
      "name": "Los Angeles Lakers",
      "subscription_tier": "pro"
    }
  },
  "token": "1|abcdefghijklmnopqrstuvwxyz1234567890",
  "token_type": "Bearer"
}
```

**Validation Errors:** `422 Unprocessable Entity`
```json
{
  "errors": {
    "email": ["The email has already been taken."],
    "password": ["The password must be at least 8 characters."]
  }
}
```

---

### 2. Login

**Endpoint:** `POST /api/login`

**Request:**
```json
{
  "email": "john@example.com",
  "password": "password123",
  "device_name": "iPhone 15 Pro"  // optional
}
```

**Response:** `200 OK`
```json
{
  "user": {
    "id": 5,
    "name": "John Scout",
    "email": "john@example.com",
    "role": "scout",
    "organization": {
      "id": 1,
      "name": "Los Angeles Lakers",
      "subscription_tier": "pro",
      "advanced_analytics_enabled": true
    }
  },
  "token": "2|xyz123abc456def789",
  "token_type": "Bearer"
}
```

**Invalid Credentials:** `401 Unauthorized`
```json
{
  "message": "The provided credentials are incorrect."
}
```

---

### 3. Get Current User

**Endpoint:** `GET /api/user`

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**Response:** `200 OK`
```json
{
  "user": {
    "id": 5,
    "name": "John Scout",
    "email": "john@example.com",
    "role": "scout",
    "organization": {
      "id": 1,
      "name": "Los Angeles Lakers",
      "subscription_tier": "pro"
    }
  }
}
```

---

### 4. Logout

**Endpoint:** `POST /api/logout`

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**Response:** `200 OK`
```json
{
  "message": "Successfully logged out"
}
```

**Note:** This revokes the current access token. The user will need to login again.

---

## Using Authentication Tokens

### In HTTP Requests

All protected endpoints require the token in the `Authorization` header:

```http
GET /api/teams HTTP/1.1
Host: api.basketballspy.com
Authorization: Bearer 1|abcdefghijklmnopqrstuvwxyz
Accept: application/json
```

### In JavaScript/React Native

```javascript
const token = await AsyncStorage.getItem('auth_token');

const response = await fetch('https://api.basketballspy.com/api/teams', {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json',
  },
});
```

### In cURL

```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Accept: application/json" \
     https://api.basketballspy.com/api/teams
```

---

## Token Management

### Token Security

**Storage:**
- ✅ Store tokens in secure storage (iOS Keychain, Android Keystore)
- ❌ Never store tokens in plain text AsyncStorage
- ❌ Never log tokens to console in production

**Best Practices:**
- Use different tokens for different devices
- Implement token refresh if sessions expire
- Clear token on logout
- Handle 401 responses by redirecting to login

### Device-Specific Tokens

You can create multiple tokens for different devices:

```json
{
  "email": "john@example.com",
  "password": "password123",
  "device_name": "iPhone 15 Pro"
}
```

This helps track which device is making requests and allows selective token revocation.

---

## Error Handling

### Unauthenticated (401)

**Scenario:** Token missing or invalid

**Response:**
```json
{
  "message": "Unauthenticated"
}
```

**Action:** Redirect user to login screen

### Unauthorized (403)

**Scenario:** User doesn't have required role

**Response:**
```json
{
  "message": "Unauthorized. Required role(s): org_admin, super_admin"
}
```

**Action:** Show error message, hide restricted features in UI

---

## User Roles

Basketball Spy has three user roles:

### Scout (Default)
- Can create and edit their own reports
- Can view teams and players
- Can view their own analytics
- Cannot see other scouts' reports

### Organization Admin (org_admin)
- All scout permissions
- Can view all reports in their organization
- Can view organization analytics
- Can manage scouts in their organization

### Super Admin (super_admin)
- All org admin permissions
- Can view all organizations
- Can view system-wide analytics
- Can manage subscription tiers

---

## Mobile App Integration Example

### Complete Auth Flow

```javascript
// 1. Login
async function login(email, password) {
  try {
    const response = await fetch('https://api.basketballspy.com/api/login', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        email,
        password,
        device_name: await Device.deviceName,
      }),
    });

    const data = await response.json();

    if (!response.ok) {
      throw new Error(data.message || 'Login failed');
    }

    // Store token securely
    await SecureStore.setItemAsync('auth_token', data.token);
    await AsyncStorage.setItem('user', JSON.stringify(data.user));

    return data;
  } catch (error) {
    console.error('Login error:', error);
    throw error;
  }
}

// 2. Make authenticated request
async function fetchTeams() {
  const token = await SecureStore.getItemAsync('auth_token');

  const response = await fetch('https://api.basketballspy.com/api/teams', {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json',
    },
  });

  if (response.status === 401) {
    // Token expired or invalid, redirect to login
    await logout();
    navigation.navigate('Login');
    return;
  }

  return await response.json();
}

// 3. Logout
async function logout() {
  const token = await SecureStore.getItemAsync('auth_token');

  try {
    await fetch('https://api.basketballspy.com/api/logout', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
      },
    });
  } catch (error) {
    console.error('Logout error:', error);
  } finally {
    // Always clear local data
    await SecureStore.deleteItemAsync('auth_token');
    await AsyncStorage.removeItem('user');
    navigation.navigate('Login');
  }
}
```

---

## Testing Authentication

### Using Postman

1. **Login:**
   - Method: POST
   - URL: `{{base_url}}/api/login`
   - Body (JSON):
     ```json
     {
       "email": "test@example.com",
       "password": "password"
     }
     ```
   - Save response token to environment variable

2. **Use Token:**
   - Add to all requests in Headers:
     - Key: `Authorization`
     - Value: `Bearer {{token}}`

### Using cURL

```bash
# Login and save token
TOKEN=$(curl -X POST https://api.basketballspy.com/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password"}' \
  | jq -r '.token')

# Use token
curl -H "Authorization: Bearer $TOKEN" \
     https://api.basketballspy.com/api/teams
```

---

## Security Best Practices

### For API Consumers

1. **Never expose tokens**
   - Don't log tokens in production
   - Don't commit tokens to git
   - Don't store in plain text

2. **Handle token expiration**
   - Implement automatic logout on 401
   - Clear tokens on app uninstall
   - Prompt user to re-login when needed

3. **Use HTTPS only**
   - Never send tokens over HTTP
   - Validate SSL certificates

4. **Implement token refresh**
   - Request new token before expiration
   - Handle refresh failures gracefully

### For API Providers

1. **Token rotation**
   - Regularly expire old tokens
   - Implement token refresh mechanism

2. **Rate limiting**
   - Limit failed login attempts
   - Implement IP-based rate limiting

3. **Audit logging**
   - Log all authentication attempts
   - Track token usage patterns
   - Monitor for suspicious activity

---

## Next Steps

- [Teams API Reference](./02-teams-api.md)
- [Reports API Reference](./04-reports-api.md)
- [Analytics API Reference](./05-analytics-api.md)
