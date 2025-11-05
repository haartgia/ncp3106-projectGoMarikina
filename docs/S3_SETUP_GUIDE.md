# AWS S3 Setup Guide for Go Marikina

## Step 1: Create an AWS Account

1. Go to https://aws.amazon.com
2. Sign up for an AWS account (free tier available)
3. Verify your email and add payment method (you won't be charged on free tier)

## Step 2: Create an S3 Bucket

1. Log in to AWS Console
2. Search for "S3" in the services search bar
3. Click "Create bucket"
4. Configure:
   - **Bucket name**: `gomarikina-uploads` (must be globally unique)
   - **Region**: Choose closest to your users (e.g., `us-east-1`)
   - **Block Public Access**: UNCHECK "Block all public access"
     - ✅ Check the acknowledgment box
   - Leave other settings as default
5. Click "Create bucket"

## Step 3: Configure Bucket Policy (Make Uploaded Files Public)

1. Click on your bucket name
2. Go to "Permissions" tab
3. Scroll to "Bucket policy"
4. Click "Edit" and paste this policy:

```json
{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Sid": "PublicReadGetObject",
            "Effect": "Allow",
            "Principal": "*",
            "Action": "s3:GetObject",
            "Resource": "arn:aws:s3:::gomarikina-uploads/*"
        }
    ]
}
```

**Note**: Replace `gomarikina-uploads` with your actual bucket name!

5. Click "Save changes"

## Step 4: Create IAM User for Programmatic Access

1. In AWS Console, search for "IAM"
2. Click "Users" in left sidebar
3. Click "Create user"
4. Configure:
   - **User name**: `gomarikina-app`
   - Click "Next"
5. Set permissions:
   - Select "Attach policies directly"
   - Search for and select: `AmazonS3FullAccess`
   - Click "Next"
6. Review and click "Create user"

## Step 5: Create Access Keys

1. Click on the user you just created (`gomarikina-app`)
2. Go to "Security credentials" tab
3. Scroll to "Access keys"
4. Click "Create access key"
5. Select use case: "Application running outside AWS"
6. Click "Next"
7. Add description: "Go Marikina Wasmer deployment"
8. Click "Create access key"
9. **IMPORTANT**: Copy both:
   - Access key ID
   - Secret access key
   
   ⚠️ **Save these somewhere safe! You can't view the secret again!**

## Step 6: Configure CORS (Allow Browser Uploads - Optional)

If you want to enable direct browser uploads in the future:

1. Go to your S3 bucket
2. Click "Permissions" tab
3. Scroll to "Cross-origin resource sharing (CORS)"
4. Click "Edit" and paste:

```json
[
    {
        "AllowedHeaders": ["*"],
        "AllowedMethods": ["GET", "PUT", "POST", "DELETE"],
        "AllowedOrigins": ["https://go-marikina.wasmer.app"],
        "ExposeHeaders": ["ETag"],
        "MaxAgeSeconds": 3000
    }
]
```

5. Click "Save changes"

## Step 7: Configure Wasmer Environment Variables

In your Wasmer dashboard, add these environment variables:

```
STORAGE_METHOD=s3
S3_BUCKET=gomarikina-uploads
S3_REGION=us-east-1
S3_ACCESS_KEY=AKIAXXXXXXXXXXXXXXXX
S3_SECRET_KEY=xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

**Replace with your actual values:**
- `S3_BUCKET`: Your bucket name
- `S3_REGION`: Your chosen region
- `S3_ACCESS_KEY`: Access key from Step 5
- `S3_SECRET_KEY`: Secret key from Step 5

## Step 8: Test Your Setup

1. Deploy your app on Wasmer (should auto-deploy from GitHub)
2. Go to https://go-marikina.wasmer.app/
3. Try creating a report with an image
4. Check your S3 bucket - you should see the uploaded file!

## Pricing (Free Tier)

AWS S3 Free Tier includes:
- ✅ 5GB storage
- ✅ 20,000 GET requests per month
- ✅ 2,000 PUT requests per month
- ✅ 100GB data transfer out per month

This is plenty for your app! After 12 months, costs are minimal:
- Storage: ~$0.023 per GB/month
- Requests: Pennies per thousand

## Troubleshooting

### Images not uploading?
- Check Wasmer logs for errors
- Verify all environment variables are set correctly
- Ensure bucket policy allows public reads

### Images upload but can't be viewed?
- Check bucket policy allows public access
- Verify bucket permissions aren't blocking public access

### "Access Denied" errors?
- Check IAM user has S3 permissions
- Verify access keys are correct

## Security Best Practices

✅ **DO:**
- Use IAM user with limited permissions (only S3)
- Keep access keys in environment variables (never in code)
- Enable MFA on AWS account
- Regularly rotate access keys

❌ **DON'T:**
- Commit access keys to GitHub
- Share your secret access key
- Give full admin permissions to IAM user

## Alternative: S3-Compatible Services

If you prefer alternatives to AWS:
- **DigitalOcean Spaces** (simpler, cheaper)
- **Wasabi** (very cheap storage)
- **Backblaze B2** (cheapest option)
- **Cloudflare R2** (no egress fees!)

All work with the same code! Just set:
```
S3_ENDPOINT=https://your-endpoint.com
```

## Need Help?

Check out:
- AWS S3 Documentation: https://docs.aws.amazon.com/s3
- AWS Free Tier: https://aws.amazon.com/free
- IAM Best Practices: https://docs.aws.amazon.com/IAM/latest/UserGuide/best-practices.html
