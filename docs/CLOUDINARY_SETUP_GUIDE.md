# Cloudinary Setup Guide for Go Marikina

## Why Cloudinary?

✅ **Super Easy Setup** (5 minutes!)
✅ **Free Tier** - 25 GB storage, 25 GB bandwidth/month
✅ **No Credit Card Required** for free tier
✅ **Image Optimization** - Automatic compression
✅ **Fast CDN** - Images load quickly worldwide
✅ **Perfect for Temporary/Demo Sites**

---

## Step 1: Create Free Cloudinary Account

1. Go to https://cloudinary.com/users/register_free
2. Sign up with email or Google account
3. Verify your email
4. **Done!** No credit card needed

---

## Step 2: Get Your Credentials

1. After logging in, you'll see your **Dashboard**
2. Look for the **Account Details** section (usually at top)
3. You'll see three important values:
   - **Cloud Name**: `dxxxxxxxxxxxxxx`
   - **API Key**: `123456789012345`
   - **API Secret**: `xxxxxxxxxxxxxxxxxxxxxx` (click the eye icon to reveal)

4. **Copy these three values** - you'll need them!

---

## Step 3: Configure Wasmer Environment Variables

In your Wasmer app dashboard:

1. Go to **Settings** → **Environment Variables**
2. Add these three variables:

```
STORAGE_METHOD=cloudinary
CLOUDINARY_CLOUD_NAME=your-cloud-name-here
CLOUDINARY_API_KEY=your-api-key-here
CLOUDINARY_API_SECRET=your-api-secret-here
```

**Replace with your actual values from Step 2!**

3. Save changes
4. Wasmer will automatically redeploy

---

## Step 4: Test It!

1. Go to https://go-marikina.wasmer.app/
2. Log in or register
3. Create a report with an image
4. ✅ The image should upload to Cloudinary!

Check your Cloudinary dashboard - you'll see the uploaded images under "Media Library"

---

## Free Tier Limits

Your free account includes:

| Feature | Limit | Notes |
|---------|-------|-------|
| **Storage** | 25 GB | Plenty for hundreds of images |
| **Bandwidth** | 25 GB/month | Viewing images |
| **Transformations** | 25 credits/month | Image processing |
| **Images** | Unlimited | No limit on number of files! |

For a small/demo site, you'll likely never hit these limits! 🎉

---

## Cloudinary Dashboard Features

Once set up, you can:

- 📁 **Browse all uploaded images** in Media Library
- 🗑️ **Delete old images** manually if needed
- 📊 **View usage statistics**
- 🖼️ **Transform images** (resize, crop, etc.)
- 📦 **Create folders** to organize images

---

## Advantages vs S3

| Feature | Cloudinary | AWS S3 |
|---------|-----------|--------|
| Setup Time | ⚡ 5 minutes | ⏰ 20+ minutes |
| Credit Card | ❌ Not required | ✅ Required |
| Image Optimization | ✅ Automatic | ❌ Manual |
| CDN | ✅ Built-in | ❌ Needs CloudFront |
| Free Tier | ✅ Forever | ⏰ 12 months only |
| Dashboard UI | ⭐⭐⭐⭐⭐ Simple | ⭐⭐⭐ Complex |
| Perfect For | Small/Demo sites | Enterprise apps |

---

## Image URLs

Your uploaded images will have URLs like:
```
https://res.cloudinary.com/your-cloud-name/image/upload/v1234567890/reports/abc123.jpg
```

These URLs:
- ✅ Work immediately
- ✅ Are fast (global CDN)
- ✅ Are permanent (until you delete them)
- ✅ Support transformations in URL

---

## Troubleshooting

### Images not uploading?

1. Check Wasmer logs for errors
2. Verify all 3 environment variables are set correctly
3. Make sure `STORAGE_METHOD=cloudinary` (not `local` or `s3`)
4. Check your Cloudinary dashboard for API key

### "Invalid signature" error?

- Double-check your `CLOUDINARY_API_SECRET` (no extra spaces!)
- Make sure you copied the full secret key

### Images upload but show broken?

- This shouldn't happen with Cloudinary
- Check the URL in your browser
- Verify the image appears in Cloudinary Media Library

---

## Upgrading Later

If your site grows and you need more:

- **Plus Plan**: $89/month - 90 GB storage, 150 GB bandwidth
- **Advanced Plan**: $224/month - 190 GB storage, 300 GB bandwidth

But the **free tier is perfect** for your temporary/demo site! 🎉

---

## Security Notes

✅ **DO:**
- Keep API credentials in environment variables
- Use the API secret (never expose it in frontend)
- Regularly check your usage in dashboard

❌ **DON'T:**
- Commit API credentials to GitHub
- Share your API secret publicly
- Use unsigned uploads (security risk)

---

## Alternative Services (Also Easy)

If you want alternatives:
- **Imgbb** - Free unlimited storage (image hosting only)
- **ImageKit** - 20 GB free, similar to Cloudinary
- **Uploadcare** - 3 GB free, simple API

But Cloudinary is the best balance of features and ease! 👍

---

## Need Help?

- Cloudinary Docs: https://cloudinary.com/documentation
- Support: https://support.cloudinary.com/
- Community: https://community.cloudinary.com/

---

## That's It! 🎉

Just three environment variables and you're done. No complex AWS setup, no bucket policies, no IAM users. **Simple and perfect for temporary sites!**
