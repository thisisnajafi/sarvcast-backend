# SSH Key Setup Instructions

## Your SSH Keys

### Private Key (for GitHub Secrets)
Copy this **ENTIRE** key to GitHub Secret `SSH_PRIVATE_KEY`:

```
-----BEGIN OPENSSH PRIVATE KEY-----
b3BlbnNzaC1rZXktdjEAAAAABG5vbmUAAAAEbm9uZQAAAAAAAAABAAAAMwAAAAtzc2gtZW
QyNTUxOQAAACCmUMgUH0mZDqJYpqgmhaBrWQd39adoQXl0mU/dvYDJkAAAAKCA5eLSgOXi
0gAAAAtzc2gtZWQyNTUxOQAAACCmUMgUH0mZDqJYpqgmhaBrWQd39adoQXl0mU/dvYDJkA
AAAECjRPLe3l7FwMZ9n0KtMOUmgzyYmIEXAb3aDzY4kWuicqZQyBQfSZkOolimqCaFoGtZ
B3f1p2hBeXSZT929gMmQAAAAGnRoaXNpc3Byb2ZuYWphZmlAeWFob28uY29tAQID
-----END OPENSSH PRIVATE KEY-----
```

### Public Key (for Server)
Add this public key to your server's `~/.ssh/authorized_keys`:

```
ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIKZQyBQfSZkOolimqCaFoGtZB3f1p2hBeXSZT929gMmQ thisisprofnajafi@yahoo.com
```

## Step-by-Step Setup

### Step 1: Add Private Key to GitHub Secrets

1. Go to: https://github.com/thisisnajafi/sarvcast-backend/settings/secrets/actions
2. Find or create `SSH_PRIVATE_KEY`
3. Click **Edit** (or **New repository secret** if it doesn't exist)
4. **Copy the ENTIRE private key above** (from `-----BEGIN` to `-----END`)
5. Paste it into the value field
6. Click **Update secret** (or **Add secret**)

### Step 2: Add Public Key to Your Server

**Option A: Using ssh-copy-id (if you have password access)**
```bash
ssh-copy-id -i C:\Users\Abolfazl\.ssh\id_ed25519.pub your-username@your-server.com
```

**Option B: Manual (if you only have SSH key access)**
1. Display your public key:
   ```bash
   type C:\Users\Abolfazl\.ssh\id_ed25519.pub
   ```
2. SSH into your server
3. Run:
   ```bash
   mkdir -p ~/.ssh
   chmod 700 ~/.ssh
   echo "ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIKZQyBQfSZkOolimqCaFoGtZB3f1p2hBeXSZT929gMmQ thisisprofnajafi@yahoo.com" >> ~/.ssh/authorized_keys
   chmod 600 ~/.ssh/authorized_keys
   ```

### Step 3: Verify SSH Connection

Test the connection from your local machine:
```bash
ssh -i C:\Users\Abolfazl\.ssh\id_ed25519 your-username@your-server.com
```

If it works, you're all set!

### Step 4: Configure GitHub Secrets

Make sure these secrets are set in GitHub:

- `SSH_HOST` - Your server hostname (e.g., `2997021731.cloudylink.com`)
- `SSH_USERNAME` - Your SSH username (e.g., `my@sarvcast.ir`)
- `SSH_PRIVATE_KEY` - The private key shown above
- `SSH_PORT` (optional) - SSH port, defaults to 22
- `APP_PATH` (optional) - Application path, defaults to `/public_html/my`

## Troubleshooting

### If SSH connection fails:
1. Verify public key is in server's `~/.ssh/authorized_keys`
2. Check file permissions on server:
   ```bash
   chmod 700 ~/.ssh
   chmod 600 ~/.ssh/authorized_keys
   ```
3. Check server SSH logs: `tail -f /var/log/auth.log` (or similar)

### If GitHub Actions still fails:
1. Verify the private key in GitHub Secrets starts with `-----BEGIN`
2. Verify it ends with `-----END`
3. Make sure there are no extra spaces before/after
4. Check the workflow logs for detailed error messages

---

**Key Location**: `C:\Users\Abolfazl\.ssh\id_ed25519`
**Key Type**: ED25519
**Fingerprint**: SHA256:HLIAvObAtdOruBMu7MDK4W/6e3b4p2Y3us3WvNelKpc

