# Vercel Deployment Guide for OnlineBizPermit

This guide explains how to deploy the OnlineBizPermit system to Vercel with PHP backend and database migration.

## Prerequisites

1. **Vercel Account**: Sign up at [vercel.com](https://vercel.com)
2. **PlanetScale Account**: Sign up at [planetscale.com](https://planetscale.com) for MySQL database
3. **GitHub Repository**: Push your code to a GitHub repository

## Step 1: Database Migration

### Export from InfinityFree
1. Log into your InfinityFree control panel
2. Go to phpMyAdmin
3. Select your database (`if0_40313162_onlinebizpermit`)
4. Click "Export" and download the SQL file

### Set up PlanetScale Database
1. Create a PlanetScale account
2. Create a new database
3. Import your SQL export:
   ```bash
   psql import mysql://<connection-string> < your-export.sql
   ```
4. Note down the connection details (host, username, password, database name)

## Step 2: Vercel Blob Setup (Optional for File Uploads)

1. In your Vercel dashboard, go to your project settings
2. Enable Vercel Blob storage
3. Get the BLOB_READ_WRITE_TOKEN from environment variables
4. Set STORAGE_TYPE=blob in environment variables

## Step 3: Deploy to Vercel

### Connect Repository
1. Go to Vercel dashboard
2. Click "New Project"
3. Import your GitHub repository
4. Configure project:
   - **Framework Preset**: Other
   - **Root Directory**: ./
   - **Build Command**: Leave empty
   - **Output Directory**: Leave empty

### Environment Variables
Set these in Vercel project settings:

```
DB_HOST=your-planetscale-host
DB_USER=your-planetscale-user
DB_PASS=your-planetscale-password
DB_NAME=your-planetscale-database
STORAGE_TYPE=local  # or 'blob' if using Vercel Blob
BLOB_READ_WRITE_TOKEN=your-blob-token  # if using blob
```

### Deploy
1. Click "Deploy"
2. Wait for deployment to complete
3. Your app will be available at `your-project.vercel.app`

## Step 4: Post-Deployment Configuration

### Update File Uploads (if using local storage)
- Files will be stored temporarily in Vercel functions
- For production, migrate to Vercel Blob or external storage

### Custom Domain (Optional)
1. Go to project settings in Vercel
2. Add your custom domain
3. Configure DNS as instructed

### Database Sessions
- The app now uses database-backed sessions for serverless compatibility
- The `user_sessions` table is created automatically

## Troubleshooting

### Database Connection Issues
- Verify PlanetScale connection string
- Ensure IP whitelisting if required
- Check environment variables are set correctly

### File Upload Issues
- If using local storage, files may not persist across deployments
- Switch to Vercel Blob for persistent file storage

### Session Issues
- Clear browser cookies if sessions don't work
- Check that `user_sessions` table exists in database

## File Structure Changes

- `vercel.json`: Vercel configuration for PHP runtime
- `session_handler.php`: Database-backed session management
- `file_upload_helper.php`: Cloud storage helper for uploads
- `db.php`: Updated with environment variables and includes

## Security Notes

- Never commit database credentials to Git
- Use environment variables for all sensitive data
- Regularly rotate API tokens and passwords
- Enable Vercel's security headers (already configured in vercel.json)

## Support

If you encounter issues:
1. Check Vercel function logs in dashboard
2. Verify environment variables
3. Test database connectivity
4. Review PHP error logs

The application is now configured for serverless deployment on Vercel!
