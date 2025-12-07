# SSH Key Format Guide for GitHub Actions

## Common Error: "error in libcrypto" or "Permission denied"

This error typically occurs when the SSH private key is not properly formatted in GitHub Secrets.

## ✅ Correct SSH Key Format

When adding your SSH private key to GitHub Secrets, you must include the **entire key** with proper line breaks:

```
-----BEGIN OPENSSH PRIVATE KEY-----
b3BlbnNzaC1rZXktdjEAAAAABG5vbmUAAAAEbm9uZQAAAAAAAAABAAABlwAAAAdzc2gtcn
NhAAAAAwEAAQAAAYEAy... (many more lines)
...
-----END OPENSSH PRIVATE KEY-----
```

OR for RSA keys:

```
-----BEGIN RSA PRIVATE KEY-----
MIIEpAIBAAKCAQEAy... (many more lines)
...
-----END RSA PRIVATE KEY-----
```

## ❌ Common Mistakes

1. **Missing BEGIN/END markers** - The key must start with `-----BEGIN` and end with `-----END`
2. **Wrong line breaks** - Each line should be on a separate line
3. **Extra spaces** - No leading/trailing spaces
4. **Copying only part of the key** - Must include ALL lines
5. **Wrong key type** - Make sure you're using the private key, not the public key

## 🔧 How to Fix

### Step 1: Get Your Private Key

```bash
# Display your private key
cat ~/.ssh/id_rsa
# OR
cat ~/.ssh/id_ed25519
```

### Step 2: Copy the ENTIRE Key

1. Copy from `-----BEGIN` to `-----END` (inclusive)
2. Include ALL lines between them
3. Don't add or remove any characters

### Step 3: Add to GitHub Secrets

1. Go to your repository on GitHub
2. **Settings** → **Secrets and variables** → **Actions**
3. Click **New repository secret**
4. Name: `SSH_PRIVATE_KEY`
5. **Paste the ENTIRE key** (including BEGIN/END lines)
6. Click **Add secret**

## 🔍 Verify Key Format

After adding the secret, you can verify it's correct by checking the workflow logs. The key should:
- Start with `-----BEGIN`
- End with `-----END`
- Have multiple lines in between
- Not have any extra characters or spaces

## 🔄 Alternative: Generate New Key Pair

If you continue having issues, generate a new key pair:

```bash
# Generate new SSH key
ssh-keygen -t ed25519 -C "github-actions@sarvcast.ir" -f ~/.ssh/github_actions_deploy

# This creates:
# - ~/.ssh/github_actions_deploy (private key) → Add to GitHub Secrets
# - ~/.ssh/github_actions_deploy.pub (public key) → Add to server

# Add public key to server
ssh-copy-id -i ~/.ssh/github_actions_deploy.pub user@your-server.com

# Or manually:
cat ~/.ssh/github_actions_deploy.pub | ssh user@your-server.com "mkdir -p ~/.ssh && cat >> ~/.ssh/authorized_keys"
```

## 📋 Quick Checklist

- [ ] Private key starts with `-----BEGIN`
- [ ] Private key ends with `-----END`
- [ ] All lines between BEGIN/END are included
- [ ] No extra spaces or characters
- [ ] Public key is added to server's `~/.ssh/authorized_keys`
- [ ] File permissions on server are correct (600 for authorized_keys, 700 for .ssh)

## 🛠️ Server-Side Verification

On your server, verify the public key is correctly added:

```bash
# Check authorized_keys
cat ~/.ssh/authorized_keys

# Verify permissions
ls -la ~/.ssh/
# Should show:
# drwx------ .ssh
# -rw------- authorized_keys
```

## 🔐 Security Note

- Never share your private key
- Never commit private keys to git
- Use a dedicated key pair for GitHub Actions
- Rotate keys periodically

---

**Last Updated**: 2024-01-XX

