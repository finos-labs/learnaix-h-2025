# 🛠️ Setting a Specific Email for a Git Repository

To specify the **email address** for your git client to use for commit in a Git repository, first make sure it has been added to your [GitHub account in Settings->Emails](https://github.com/settings/emails). Afterwards, you can configure your git client locally (i.e. for just that repository) using the following commands:

```bash
# Navigate to your repository
cd path/to/your/repo

# Set the email address to use for commits (local to this repo only)
git config --local user.email "your-secondary-email@example.com"

# Set your name for commits
git config --local user.name "Your Name"

# Verify the local config
git config user.email
git config user.name
```

&nbsp;

## ✅ Enable DCO Sign-Off for Commits (Local Only)

This project follows the [Developer Certificate of Origin](https://developercertificate.org) (DCO) process.

### 🖊️ To sign off a single commit manually

```bash
git commit -s -m "feat: initial commit with signed-off"
```

The `-s` flag (ask `--signoff`) adds a Signed-off-by line to your commit message.

### 🔁 Optional: Create a local alias for signed commits

Set an alias to always sign off when committing:

```bash
git config --local alias.ci 'commit -s'
```

Then you can use:

```bash
git ci -m "feat: add new feature"
```

&nbsp;

## Helpful DCO Resources

- [Git Tools - Signing Your Work](https://git-scm.com/book/en/v2/Git-Tools-Signing-Your-Work)
- [Signing commits
  ](https://docs.github.com/en/github/authenticating-to-github/signing-commits)
