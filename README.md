# AI Comment Guard

[![WordPress Plugin Version](https://img.shields.io/wordpress/plugin/v/ai-comment-guard)](https://wordpress.org/plugins/ai-comment-guard/)
[![WordPress Tested](https://img.shields.io/wordpress/v/ai-comment-guard)](https://wordpress.org/plugins/ai-comment-guard/)
[![PHP Version](https://img.shields.io/badge/PHP-%3E%3D7.2-8892BF.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-GPL--2.0%2B-blue.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

🤖 **AI-powered comment moderation for WordPress** - Protect your WordPress site from spam with intelligent, customizable AI analysis.

## 🌟 Features

- **🔌 Multiple AI Providers**: OpenAI (GPT-4/GPT-3.5), Anthropic (Claude), OpenRouter
- **⚡ Automatic Processing**: Real-time comment analysis and moderation
- **🎯 Smart Classification**: Auto-approve, reject, hold, or mark as spam
- **📊 Confidence Thresholds**: Customizable confidence levels for actions
- **✏️ Custom Prompts**: Tailor AI behavior to your needs
- **📈 Comprehensive Logging**: Track all decisions with detailed statistics
- **🔒 Secure Integration**: Encrypted API key storage
- **🌍 i18n Ready**: Fully translatable
- **⚙️ Easy Setup**: Intuitive admin interface

## 📋 Requirements

- WordPress 5.0+
- PHP 7.2+
- API key from supported provider
- SSL certificate (recommended)

## 🚀 Installation

### From WordPress Admin

1. Navigate to **Plugins > Add New**
2. Search for "AI Comment Guard"
3. Click **Install Now** then **Activate**
4. Go to **Settings > AI Comment Guard**

### Manual Installation

1. Download the latest release
2. Upload to `/wp-content/plugins/ai-comment-guard/`
3. Activate through the **Plugins** menu
4. Configure at **Settings > AI Comment Guard**

## ⚙️ Configuration

1. **Choose Provider**: Select OpenAI, Anthropic, or OpenRouter
2. **Add API Key**: Enter your provider's API key
3. **Test Connection**: Verify the connection works
4. **Set Thresholds**: Configure confidence levels
5. **Customize Prompts**: Tailor AI analysis criteria
6. **Enable Logging**: Track moderation decisions (optional)

## 🔑 API Providers

### OpenAI
- Get your key at [platform.openai.com](https://platform.openai.com/api-keys)
- Supports GPT-4 and GPT-3.5 models
- Excellent for nuanced content analysis

### Anthropic
- Get your key at [console.anthropic.com](https://console.anthropic.com/)
- Uses Claude models
- Great for context-aware moderation

### OpenRouter
- Get your key at [openrouter.ai](https://openrouter.ai/)
- Access to multiple AI models
- Flexible pricing options

## 📊 Usage Examples

### Basic Setup
```php
// The plugin works automatically once configured
// Comments are processed before being saved to database
```

### Custom Threshold Example
- **Spam Threshold**: 0.7 (70% confidence = mark as spam)
- **Approval Threshold**: 0.3 (30% confidence = auto-approve)
- **Between thresholds**: Hold for manual review

### Custom Prompt Example
```
Analyze this comment for spam, inappropriate content, or legitimate discussion.
Consider: relevance, tone, promotional content, and value to discussion.
Respond with JSON: {"analysis": "approved|rejected|spam", "confidence": 0.0-1.0, "reason": "explanation"}
```

## 🛡️ Security Features

- **Nonce Verification**: All AJAX requests protected
- **Capability Checks**: Admin-only access to settings
- **Data Sanitization**: All inputs properly sanitized
- **SQL Injection Protection**: Prepared statements used
- **XSS Prevention**: Output properly escaped
- **HTTPS Only**: API communications encrypted

## 🌐 Internationalization

The plugin is fully translatable with `.pot` file included. Available text domains:
- `ai-comment-guard`

To translate:
1. Use the included `.pot` file
2. Create your `.po` and `.mo` files
3. Place in `/wp-content/languages/plugins/`

## 📈 Performance

- **Async Processing**: Non-blocking comment analysis
- **Database Indexes**: Optimized query performance
- **Caching**: Configuration cached for efficiency
- **Cleanup Cron**: Automatic old log removal
- **Minimal Overhead**: < 50ms average processing time

## 🔧 Development

### File Structure
```
ai-comment-guard/
├── admin/              # Admin interface assets
│   ├── css/           # Admin styles
│   └── js/            # Admin scripts
├── includes/          # Core PHP classes
│   ├── AI/           # AI provider implementations
│   ├── Admin/        # Admin functionality
│   ├── Comments/     # Comment processing
│   ├── Core/         # Core plugin files
│   ├── Database/     # Database operations
│   └── Utils/        # Utility classes
├── languages/         # Translation files
├── ai-comment-guard.php  # Main plugin file
├── readme.txt         # WordPress.org readme
├── README.md          # This file
└── uninstall.php      # Clean uninstall handler
```

### Coding Standards

- **PSR-4 Autoloading**: Modern PHP namespace structure
- **WordPress Coding Standards**: Following WP guidelines
- **PHPDoc Comments**: Complete documentation
- **Security Best Practices**: OWASP guidelines followed
- **Design Patterns**: Singleton, Factory, Strategy patterns

### Hooks & Filters

```php
// Filter comment approval status
add_filter('pre_comment_approved', 'your_function', 10, 2);

// Action after AI analysis
do_action('aicog_after_analysis', $comment_data, $analysis);

// Filter AI prompt
add_filter('aicog_prompt', 'customize_prompt', 10, 2);
```

## 🧪 Testing

### Manual Testing Checklist
- [ ] Install and activate plugin
- [ ] Configure API provider
- [ ] Test connection
- [ ] Submit test comment
- [ ] Verify AI analysis
- [ ] Check logs (if enabled)
- [ ] Test threshold adjustments
- [ ] Verify uninstall cleanup

## 📝 Changelog

For detailed version history and release notes, see the [changelog in readme.txt](readme.txt#changelog) or visit the [WordPress.org plugin page](https://wordpress.org/plugins/ai-comment-guard/#changelog).

## 🤝 Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Open a pull request

### Reporting Issues

Please report issues with:
- WordPress version
- PHP version
- Error messages
- Steps to reproduce

## 📜 License

AI Comment Guard is licensed under GPL v2 or later.

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.

## 👨‍💻 Author

**Tudor Constantin**
- LinkedIn: [tudor-eusebiu-constantin](https://www.linkedin.com/in/tudor-eusebiu-constantin/)

## 🙏 Acknowledgments

- WordPress Community for feedback and support
- Contributors and testers
- AI provider teams for excellent APIs

## 📞 Support

For support, please:
1. Check the [FAQ section](https://wordpress.org/plugins/ai-comment-guard/#faq)
2. Visit the [support forum](https://wordpress.org/support/plugin/ai-comment-guard/)
3. Contact via [LinkedIn](https://www.linkedin.com/in/tudor-eusebiu-constantin/)

---

⭐ **If you find this plugin useful, please consider leaving a 5-star review on WordPress.org!**
