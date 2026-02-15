# 🚀 DEPLOYMENT INSTRUCTIONS - Railway

**Status:** ✅ Project cleaned and ready  
**Date:** 2026-02-15  
**Next Step:** Follow these instructions to deploy to Railway

---

## 📋 Pre-Deployment Checklist

Before deploying, complete these steps:

### 1. ✅ Verify Files Are Cleaned

24 test/debug files have been automatically deleted:
- ✅ `debug_csrf.php` removed
- ✅ `test_*.php` files removed
- ✅ `diagnostic_complet.php` removed
- ✅ InfinityFree guide files removed
- ✅ All fix_*.php scripts removed

**Verify cleanup:**
```powershell
Get-ChildItem -Name debug*.php, test*.php, diagnostic*.php
# Should return: No items found
```

### 2. ✅ Check Documentation Is Present

Required files must exist:
```powershell
Get-ChildItem -Name README.md, QUICKSTART.md, DEPLOYMENT_RAILWAY.md, SECURITY_CHECKLIST.md, PROJECT_STATUS.md
# Should show all 5 files
```

### 3. ✅ Verify Configuration Files

Essential files:
```powershell
Get-ChildItem -Name Procfile, railway.json, composer.json, .env, .gitignore
# Should show all 5 files
```

### 4. ⚠️ Review .env Configuration

Edit `.env` and verify:
```ini
# Should have:
ENVIRONMENT=production
APP_DEBUG=false
APP_LOG_LEVEL=warning

# Database (will be set by Railway):
DB_HOST=localhost
DB_NAME=bhconnect_db

# Session timeout (3600 = 1 hour):
SESSION_TIMEOUT=3600
```

**DO NOT commit .env** - It's already in .gitignore ✅

### 5. 🔒 Security Review

Before deploying, ensure:
- [ ] No passwords in code (search results: 0 hardcoded passwords)
- [ ] APP_DEBUG is false
- [ ] .env is in .gitignore
- [ ] All test files removed ✅
- [ ] CSRF tokens enabled
- [ ] Rate limiting enabled

Check: `grep -r "password\|API_KEY" config/*.php models/*.php`
Result should be: 0 matches (only config.php requiring .env is OK)

---

## 🚀 Deployment Steps

### Step 1: Commit Code to Git

```powershell
# Check status
git status
# Should show modified files (README.md, QUICKSTART.md, etc.) and deleted test files

# Add all changes
git add .

# Commit with clear message
git commit -m "Cleanup: Remove test files and prepare for Railway deployment

- Removed 24 debug/test files
- Added comprehensive documentation
- Added Railway deployment configuration
- Ready for production deployment"

# Verify commit
git log --oneline | Select-Object -First 1
```

### Step 2: Push to GitHub

```powershell
# Push to main branch
git push origin main

# Verify push succeeded
git log --oneline -n 1
git status
# Should show: "On branch main, nothing to commit, working tree clean"
```

### Step 3: Create Railway Account (If Needed)

1. Go to https://railway.app
2. Sign up (or login)
3. Click "New Project"
4. Select "Deploy from GitHub"

### Step 4: Connect GitHub Repository

1. Authorize Railway to access GitHub
2. Select your repository: `bhconnect` (or your repo name)
3. Select branch: `main`
4. Railway will automatically detect `Procfile` and start deployment

**⏳ Wait 2-3 minutes for initial deployment**

### Step 5: Configure Database in Railway

1. In Railway dashboard, click your project
2. Click "Add Service" → "Database"
3. Choose: "MySQL" (recommended for compatibility)
4. Wait for DB to initialize (1-2 minutes)

**Note:** PostgreSQL also works - Railway will automatically set DATABASE_URL

### Step 6: Add Environment Variables

In Railway dashboard:
1. Click your web service
2. Go to "Variables" tab
3. Add these variables:

```env
ENVIRONMENT=production
APP_DEBUG=false
APP_LOG_LEVEL=warning
SESSION_TIMEOUT=3600
SESSION_NAME=bh_connect_session
CSRF_TOKEN_LENGTH=32
RATE_LIMIT_ATTEMPTS=5
RATE_LIMIT_WINDOW=300
TIMEZONE=Europe/Paris
```

**Note:** Railway automatically provides `DATABASE_URL` - no need to set DB_HOST/DB_PASS

### Step 7: Trigger Deployment

Either:

**Option A: Automatic deployment** (Recommended)
- Make any small change and push to GitHub
- Railway auto-redeploys when it detects a push
- Monitor in Railway dashboard

**Option B: Manual trigger**
- In Railway dashboard → click dropdown on web service
- Click "Redeploy"
- Wait for deployment to complete

### Step 8: Verify Deployment

1. Go to Railway dashboard
2. Copy your domain (should be `something-production.railway.app`)
3. Visit: `https://your-domain.railway.app`
4. You should see BH CONNECT login page

**If you get a "Deploy" status:**
- Wait 2-3 minutes
- Refresh the page
- Check the Logs tab for errors

### Step 9: Test Critical Functions

```
Test Checklist:
[ ] Visit https://your-domain.railway.app - shows login page
[ ] Register new account - completes successfully
[ ] Login - CSRF token works, session created
[ ] Access dashboard - for your role (Admin/Agent/Client)
[ ] View dossier - for clients/agents
[ ] Upload document - test file upload
[ ] Create RDV - for agents/admins
```

### Step 10: Set Up Custom Domain (Optional)

If you have a custom domain:
1. Railway dashboard → Settings
2. Click "Domains"
3. Add custom domain
4. Update DNS records (Railway provides instructions)
5. SSL is automatic ✅

---

## 📈 Post-Deployment Tasks

### Week 1: Monitor & Test

1. **Check Logs Daily**
   ```
   Railway Dashboard → Your Service → Logs tab
   Should see minimal errors (warnings OK)
   ```

2. **Test All Features**
   - Complete registration flow
   - Test each role (Admin/Agent/Client)
   - Upload documents
   - Create rendez-vous
   - Generate factures

3. **Monitor Performance**
   - Response times
   - Database connection status
   - Error frequency

### Set Up Monitoring (Uptime Robot)

1. Go to https://uptimerobot.com
2. Create new HTTP monitor
3. URL: `https://your-domain.railway.app`
4. Frequency: 5 minutes
5. Notification: Email

### Database Backups

1. Railway dashboard → Database service
2. Go to "Backups" tab
3. Enable automatic backups (weekly minimum)
4. Download backups regularly

### Review Security

Use checklist: [SECURITY_CHECKLIST.md](./SECURITY_CHECKLIST.md)
- [ ] All items verified
- [ ] No hardcoded credentials
- [ ] HTTPS working
- [ ] Error logs clean

---

## 🚨 Troubleshooting

### ❌ Deployment Failed

**Check logs:**
```
Railway Dashboard → Service → Logs
Look for error messages
```

**Common issues:**
- PHP version issue → Check Procfile
- Missing startup command → railway.json
- Database not initialized → Run db_init script

### ❌ "502 Bad Gateway"

Usually means app crashed:
1. Check Railway logs
2. Check database connection
3. Verify environment variables set
4. Verify charset=utf8mb4 on database

### ❌ Can't connect to database

1. Verify DATABASE_URL is set (Railway dashboard → Variables)
2. Check database service is running (Railway dashboard)
3. Run database setup again if needed

### ❌ "Jeton de sécurité invalide" (CSRF error)

Session/cookie issue:
1. Clear browser cookies
2. Check SESSION_TIMEOUT in variables
3. Verify cookies are not disabled in browser

### ❌ File upload failing

1. Check `uploads/` directory permissions (755)
2. Upload file smaller than 10MB
3. Verify file type is allowed (PDF, JPG, PNG, DOC)

---

## 📞 Get Help

### Documentation
- [README.md](./README.md) - Project overview
- [QUICKSTART.md](./QUICKSTART.md) - Quick start guide
- [DEPLOYMENT_RAILWAY.md](./DEPLOYMENT_RAILWAY.md) - Detailed deployment guide
- [SECURITY_CHECKLIST.md](./SECURITY_CHECKLIST.md) - Security checklist
- [PROJECT_STATUS.md](./PROJECT_STATUS.md) - Project status

### Command Line Debug

```bash
# Test locally before deploying
cp .env.example .env
# Edit .env locally

# Run tests
php -S localhost:8000

# Check errors
cat logs/php_errors.log
cat logs/database_error.log
```

### Railway Support
- Documentation: https://docs.railway.app
- Status page: https://status.railway.app
- Community: https://discord.gg/railway

---

## ✅ Final Checklist Before Production

- [ ] Code committed and pushed to GitHub
- [ ] GitHub branch is `main`
- [ ] Railway project created and configured
- [ ] Database service initialized
- [ ] Environment variables set
- [ ] Deployment completed successfully
- [ ] Login page loads at your domain
- [ ] Registration works
- [ ] Can login and access dashboard
- [ ] Monitoring/Uptime Robot configured
- [ ] Backups enabled
- [ ] Team notified of go-live

---

## 🎉 Congratulations!

Your BH CONNECT application is now live on Railway!

### What's Running
- ✅ Web application at https://your-domain.railway.app
- ✅ MySQL database (automatic backups enabled)
- ✅ HTTPS/SSL (automatic, Railway-managed)
- ✅ Error logging (Railway logs + local logs/)
- ✅ Session management (PHP native)
- ✅ Rate limiting (5 attempts/5 min)
- ✅ CSRF protection (all forms)

### Next Steps
- Monitor logs daily for first week
- Gather user feedback
- Plan feature enhancements
- Review security regularly

---

**Questions?** Refer to the documentation files or reach out to your team.

**Ready to go live!** 🚀
