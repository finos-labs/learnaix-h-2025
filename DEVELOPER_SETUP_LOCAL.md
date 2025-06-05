## 🛠️ Setting a Secondary GitHub Email for a Local Git Repository

To use a **secondary GitHub email** in a specific Git repository, you can configure it locally using the following commands:

```bash
# Navigate to your repository
cd path/to/your/repo

# Set your secondary GitHub email (local to this repo only)
git config --local user.email "your-secondary-email@example.com"

# Sset your name for commits
git config --local user.name "Your Name"

# Verify the local config
git config user.email
git config user.name
```

## ✅ Enable DCO Sign-Off for Commits (Local Only)

This project follows the [Developer Certificate of Origin](https://developercertificate.org) (DCO) process.

### 🖊️ To sign off a single commit manually

```bash
git commit -s -m "feat: initial commit with signed-off"
```

This adds a Signed-off-by line to your commit message.

### 🔁 Optional: Create a local alias for signed commits

Set an alias to always sign off when committing:

```bash
git config --local alias.ci 'commit -s'
```

Then you can use:

```bash
git ci -m "feat: add new feature"
```
