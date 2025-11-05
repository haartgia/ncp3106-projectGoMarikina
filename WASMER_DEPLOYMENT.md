# Go Marikina - Wasmer Deployment Guide

## Image Storage Configuration for Wasmer Hosting

Since Wasmer uses an ephemeral filesystem (files don't persist between deployments), you need to configure external storage for user-uploaded images.

### Option 1: Cloudinary (⭐ RECOMMENDED for Temporary Sites)

**Pros:** 
- ⚡ Super easy 5-minute setup
- 🎁 Free tier (no credit card needed)
- 🖼️ Automatic image optimization
- 🚀 Fast global CDN
- 📱 Great dashboard UI

**Cons:** Free tier limits (25 GB storage, 25 GB/month bandwidth)

**Setup:**
1. Sign up at https://cloudinary.com/users/register_free
2. Get your Cloud Name, API Key, and API Secret
3. Set environment variables in Wasmer:
```
STORAGE_METHOD=cloudinary
CLOUDINARY_CLOUD_NAME=your-cloud-name
CLOUDINARY_API_KEY=your-api-key
CLOUDINARY_API_SECRET=your-api-secret
```

📖 **Full Guide:** See `docs/CLOUDINARY_SETUP_GUIDE.md`

### Option 2: Base64 Storage in Database (Quick Test)

**Pros:** No external service needed, works immediately
**Cons:** Increases database size, not ideal for many/large images

Set environment variable in Wasmer:
```
STORAGE_METHOD=base64
```

### Option 3: AWS S3 Storage (Best for Production)

**Pros:** Scalable, fast, professional
**Cons:** Requires AWS account and configuration

1. Create an S3 bucket on AWS
2. Set environment variables in Wasmer:
```
STORAGE_METHOD=s3
S3_BUCKET=your-bucket-name
S3_REGION=us-east-1
S3_ACCESS_KEY=your-access-key
S3_SECRET_KEY=your-secret-key
```

### Option 3: Cloudinary (Great for Images)

**Pros:** Image optimization, transformations, CDN
**Cons:** Requires Cloudinary account

1. Sign up for Cloudinary (free tier available)
2. Set environment variables in Wasmer:
```
STORAGE_METHOD=cloudinary
CLOUDINARY_CLOUD_NAME=your-cloud-name
CLOUDINARY_API_KEY=your-api-key
CLOUDINARY_API_SECRET=your-api-secret
```

### Option 4: Local Storage (Development Only)

**Warning:** Files will be deleted on Wasmer when you redeploy!

Set environment variable:
```
STORAGE_METHOD=local
```

## Setting Environment Variables in Wasmer

1. Go to your Wasmer app dashboard
2. Navigate to Settings → Environment Variables
3. Add the variables according to your chosen storage method
4. Redeploy your application

## Database Setup

Your database is already configured. Make sure these tables exist:
- users
- reports
- notifications
- announcements
- sensor_data
- password_resets
- Archive tables

Run the SQL script in `docs/sql/complete_setup.sql` if needed.

## Current Configuration

- **Database Host:** db.fr-pari1.bengt.wasmernet.com
- **Database Port:** 10272
- **Database Name:** gomarikina
- **Live URL:** https://go-marikina.wasmer.app/

## Quick Start

1. Push code to GitHub
2. Wasmer auto-deploys from your main branch
3. Set STORAGE_METHOD environment variable
4. Test image uploads on your live site

## Need Help?

- Check Wasmer logs for errors
- Verify database connection
- Test storage configuration
- Check file upload limits
