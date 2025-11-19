# AI Configuration Guide

## Overview
The system now supports three AI providers for product optimization:
- **DeepSeek AI** (Recommended - Cost Effective)
- **OpenAI** (GPT-4 / GPT-3.5)
- **Kimi AI** (Moonshot)

## Accessing AI Settings

1. Go to **Admin Dashboard** → **Settings**
2. Click on the **🤖 AI Configuration** tab
3. Configure your preferred AI provider

## Configuration Options

### Enable AI Features
Toggle this checkbox to enable/disable all AI-powered features across the system.

### Default AI Provider
Choose which AI provider to use by default:
- **DeepSeek AI**: Best balance of cost and quality
- **OpenAI**: Highest quality, more expensive
- **Kimi AI**: Good multilingual support, competitive pricing

## Provider Setup

### 1. DeepSeek AI (Recommended)

**Why DeepSeek?**
- Most cost-effective option
- Excellent performance
- Good multilingual support
- Fast response times

**Setup Steps:**
1. Visit [platform.deepseek.com](https://platform.deepseek.com)
2. Create an account or log in
3. Navigate to API Keys section
4. Generate a new API key
5. Copy the key (starts with `sk-`)
6. Paste it in the **DeepSeek API Key** field
7. Save settings

**Pricing:**
- ~$0.14 per 1M input tokens
- ~$0.28 per 1M output tokens
- Significantly cheaper than OpenAI

### 2. OpenAI

**Why OpenAI?**
- Industry-leading AI models
- Highest quality outputs
- Latest GPT-4 models available
- Most reliable for complex tasks

**Setup Steps:**
1. Visit [platform.openai.com](https://platform.openai.com)
2. Sign in to your account
3. Go to API Keys section
4. Create new secret key
5. Copy the key (starts with `sk-`)
6. Paste it in the **OpenAI API Key** field
7. Select your preferred model:
   - **GPT-4o**: Latest, fastest, most capable
   - **GPT-4o Mini**: Recommended - Cost effective
   - **GPT-4 Turbo**: High quality
   - **GPT-3.5 Turbo**: Budget option
8. Save settings

**Pricing (approximate):**
- GPT-4o Mini: $0.15/$0.60 per 1M tokens (input/output)
- GPT-4o: $2.50/$10 per 1M tokens
- GPT-4 Turbo: $10/$30 per 1M tokens
- GPT-3.5 Turbo: $0.50/$1.50 per 1M tokens

### 3. Kimi AI (Moonshot)

**Why Kimi?**
- Excellent multilingual capabilities
- Long context window (128K tokens)
- Competitive pricing
- Strong in Chinese/English

**Setup Steps:**
1. Visit [platform.moonshot.cn](https://platform.moonshot.cn)
2. Register and log in
3. Access API management
4. Generate API key
5. Copy the key
6. Paste it in the **Kimi AI API Key** field
7. Save settings

**Pricing:**
- ~$0.12 per 1M input tokens
- ~$0.12 per 1M output tokens
- Very competitive rates

## Testing Your Configuration

After entering your API keys:

1. Click **🧪 Test AI Connection** button
2. System will send a test prompt to the selected provider
3. You'll see:
   - ✅ Success message with AI response
   - ❌ Error message if connection fails

**Common Issues:**
- **Invalid API Key**: Check if key is correct
- **No Credits**: Ensure your account has credits/balance
- **Network Error**: Check internet connection

## AI Features Available

Once configured, you'll have access to:

### 1. ✨ SEO-Optimized Descriptions
- Automatically generate compelling product descriptions
- Includes relevant keywords naturally
- Optimized for search engines
- Engaging and persuasive copy

### 2. 📝 Short Description Generation
- Creates concise 1-2 sentence summaries
- Perfect for meta descriptions (160 chars)
- Includes primary keywords
- Action-oriented language

### 3. 🔑 Keyword Extraction
- Generates 10-15 relevant SEO keywords
- Includes long-tail keywords
- Market-specific terms (Kenya/Tanzania)
- Related search terms

### 4. 🎯 Selling Points Identification
- Extracts top 5 key selling points
- Focuses on benefits, not features
- Concise and impactful
- Customer-focused

### 5. 📊 Feature Generation
- Converts specifications to features
- Customer-friendly language
- Benefit-focused statements
- Highlights competitive advantages

### 6. 🏆 Title Optimization
- Creates SEO-friendly titles
- 50-60 characters optimal length
- Includes key specifications
- Natural and professional

### 7. 🌐 Alibaba Auto-Import with AI
- Scrapes product data from Alibaba
- Downloads product images locally
- Applies AI optimization to all fields
- Ready-to-publish products

## Using AI Features

### In Product Management

**Method 1: Manual Optimization**
1. Open product in edit mode
2. Fill in basic information
3. Click **AI Optimize** button
4. Select optimization type:
   - Full Optimization
   - Description Only
   - Title Only
   - Keywords Only
   - Short Description
5. AI will generate optimized content
6. Review and adjust if needed
7. Save product

**Method 2: Alibaba Import with AI**
1. Click "Add New Product"
2. Expand "Import from Alibaba" section
3. Paste Alibaba product URL
4. Click "Fetch Product"
5. System automatically:
   - Scrapes all product data
   - Downloads images
   - Applies AI optimization
   - Populates all fields
6. Review and set pricing
7. Save product

## Best Practices

### 1. Choose the Right Provider
- **DeepSeek**: Best for most use cases, very cost-effective
- **OpenAI**: When you need highest quality
- **Kimi**: Good for multilingual products

### 2. Review AI Output
- Always review generated content
- Adjust for your brand voice
- Verify accuracy of technical details
- Add market-specific information

### 3. Optimize Costs
- Use cheaper models for simple tasks
- Use GPT-4 only when needed
- Batch optimize products during off-peak
- Monitor your API usage

### 4. API Key Security
- Never share your API keys
- Store them securely in settings
- Rotate keys periodically
- Monitor for unauthorized usage

### 5. Content Quality
- Provide detailed product information for better AI output
- Include specifications and features
- The more data, the better the AI optimization
- Review and refine generated content

## Troubleshooting

### AI Not Working
**Check:**
- [ ] AI Features enabled in settings
- [ ] Valid API key entered
- [ ] API account has credits
- [ ] Internet connection stable
- [ ] No firewall blocking API calls

### Poor Quality Output
**Solutions:**
- Provide more detailed product information
- Try different AI provider
- Use higher-tier model (e.g., GPT-4 instead of GPT-3.5)
- Adjust product data before optimization

### Slow Performance
**Causes:**
- High API load
- Large product descriptions
- Network latency

**Solutions:**
- Use faster models (GPT-4o, DeepSeek)
- Optimize during off-peak hours
- Reduce description length before processing

### API Errors
**Common Errors:**
- **401 Unauthorized**: Invalid API key
- **429 Rate Limit**: Too many requests, wait and retry
- **500 Server Error**: Provider issue, try again later
- **Timeout**: Network issue or slow response

## Cost Management

### Estimated Costs Per Product

**DeepSeek AI:**
- Full optimization: ~$0.001-0.002 per product
- ~500-1000 products per $1

**OpenAI (GPT-4o Mini):**
- Full optimization: ~$0.005-0.01 per product
- ~100-200 products per $1

**OpenAI (GPT-4o):**
- Full optimization: ~$0.02-0.04 per product
- ~25-50 products per $1

**Kimi AI:**
- Full optimization: ~$0.001-0.002 per product
- ~500-1000 products per $1

### Budget Recommendations
- **Small Store** (< 100 products): $5-10/month
- **Medium Store** (100-500 products): $10-25/month
- **Large Store** (500+ products): $25-50/month

## Advanced Configuration

### Multiple Providers
You can configure all three providers and switch between them:
1. Enter API keys for multiple providers
2. Change default provider in settings
3. Test each provider
4. Use different providers for different tasks

### Model Selection (OpenAI)
- **GPT-4o**: Latest tech, fastest, best quality
- **GPT-4o Mini**: Sweet spot - good quality, affordable
- **GPT-4 Turbo**: High quality, slower
- **GPT-3.5 Turbo**: Basic tasks, cheapest

### Custom Prompts
Advanced users can modify AI prompts in:
```
admin/includes/ai_helper.php
```

Look for methods like:
- `optimizeDescription()`
- `generateShortDescription()`
- `generateSEOKeywords()`
- etc.

## Monitoring Usage

### Check Your Usage
1. **DeepSeek**: [platform.deepseek.com/usage](https://platform.deepseek.com/usage)
2. **OpenAI**: [platform.openai.com/usage](https://platform.openai.com/usage)
3. **Kimi**: [platform.moonshot.cn/console](https://platform.moonshot.cn/console)

### Set Spending Limits
- Configure spending limits in provider dashboard
- Set up billing alerts
- Monitor daily/monthly usage
- Review cost per optimization

## Support & Resources

### Documentation
- DeepSeek: [docs.deepseek.com](https://docs.deepseek.com)
- OpenAI: [platform.openai.com/docs](https://platform.openai.com/docs)
- Kimi: [platform.moonshot.cn/docs](https://platform.moonshot.cn/docs)

### Getting Help
- Test connection feature in settings
- Check provider status pages
- Review API logs
- Contact provider support

### Updates
- AI models improve regularly
- Update to latest models for better results
- Check for new features
- Monitor pricing changes

---

## Quick Start Checklist

- [ ] Choose your AI provider (DeepSeek recommended)
- [ ] Create account with provider
- [ ] Generate API key
- [ ] Enter API key in Settings → AI Configuration
- [ ] Select default provider
- [ ] Click "Test AI Connection"
- [ ] Enable AI Features
- [ ] Save settings
- [ ] Try optimizing a product
- [ ] Monitor usage and costs

**You're ready to use AI-powered product optimization!** 🚀
