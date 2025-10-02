=== AI Comment Guard ===
Contributors: tud0r
Donate link: https://www.linkedin.com/in/tudor-eusebiu-constantin/
Tags: comments, spam, moderation, ai, artificial intelligence
Requires at least: 5.0
Tested up to: 6.8
Stable tag: 1.1.0
Requires PHP: 7.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Protect your WordPress site from spam with AI-powered comment moderation. Supports OpenAI, Anthropic, and OpenRouter providers.

== Description ==

**AI Comment Guard** is a powerful WordPress plugin that uses artificial intelligence to automatically moderate comments on your website. Say goodbye to spam and inappropriate content with intelligent, customizable AI analysis.

= Key Features =

* 🤖 **Multiple AI Provider Support**: Choose from OpenAI (GPT-4/GPT-3.5), Anthropic (Claude), or OpenRouter
* ⚡ **Automatic Comment Processing**: Instantly analyze and moderate comments as they're submitted
* 🎯 **Smart Classification**: Automatically approve, reject, hold, or mark comments as spam
* 📊 **Confidence Thresholds**: Set custom confidence levels for different actions
* 📝 **Customizable AI Prompts**: Tailor the AI's behavior to your specific needs
* 📈 **Comprehensive Logging**: Track all AI decisions with detailed logs and statistics
* 🔒 **Secure API Integration**: Your API keys are stored securely
* 🌍 **Internationalization Ready**: Fully translatable to any language
* ⚙️ **Easy Configuration**: Simple setup with intuitive admin interface

= How It Works =

1. **Configure Your AI Provider**: Add your API key from OpenAI, Anthropic, or OpenRouter
2. **Set Your Preferences**: Customize thresholds and prompts to match your moderation style
3. **Let AI Do the Work**: Comments are automatically analyzed and actioned based on your settings
4. **Review and Refine**: Monitor performance through detailed logs and adjust settings as needed

= Perfect For =

* **Bloggers** who want to maintain quality discussions
* **Business Websites** needing professional comment moderation
* **High-Traffic Sites** requiring automated spam protection
* **Community Platforms** wanting consistent moderation standards
* **International Sites** needing multilingual comment analysis

= Privacy & Security =

* API keys are stored securely in your WordPress database
* No comment data is stored on third-party servers beyond AI processing
* GDPR compliant with optional logging that can be disabled
* All communications with AI providers use secure HTTPS connections

== External Services ==

This plugin connects to an external service in order to analyze and moderate comments using artificial intelligence.  
You can choose one of the following providers in the plugin settings:

1. **OpenAI API** (https://openai.com/)  
   - **Purpose:** Used to generate text analysis and classify comments.  
   - **Data sent:** The comment content (text) and moderation instructions.  
   - **When data is sent:** Each time a comment is submitted on your site and OpenAI is selected as the provider.  
   - **Where data is sent:** To OpenAI servers (https://api.openai.com/v1/chat/completions).  
   - **Policies:** [Terms of Use](https://openai.com/policies/terms-of-use), [Privacy Policy](https://openai.com/policies/privacy-policy).

2. **Anthropic API** (https://www.anthropic.com/)  
   - **Purpose:** Used to analyze and classify comments through the Claude model.  
   - **Data sent:** The comment content and analysis context.  
   - **When data is sent:** Each time a comment is submitted and Anthropic is selected as the provider.  
   - **Where data is sent:** To Anthropic servers (https://api.anthropic.com/v1/messages).  
   - **Policies:** [Terms of Service](https://www.anthropic.com/legal/consumer-terms), [Privacy Policy](https://www.anthropic.com/legal/privacy).

3. **OpenRouter API** (https://openrouter.ai/)  
   - **Purpose:** Routes requests to multiple AI models for comment analysis.  
   - **Data sent:** The comment content and parameters required for processing.  
   - **When data is sent:** Each time a comment is submitted and OpenRouter is selected as the provider.  
   - **Where data is sent:** To OpenRouter servers (https://openrouter.ai/api/v1/chat/completions).  
   - **Policies:** [Terms](https://openrouter.ai/terms), [Privacy Policy](https://openrouter.ai/privacy).

= Requirements =

* WordPress 5.0 or higher
* PHP 7.2 or higher
* An API key from OpenAI, Anthropic, or OpenRouter
* SSL certificate recommended for secure API communications

== Installation ==

= Automatic Installation =

1. Log in to your WordPress admin panel
2. Go to **Plugins > Add New**
3. Search for "AI Comment Guard"
4. Click **Install Now** and then **Activate**
5. Go to **Settings > AI Comment Guard** to configure

= Manual Installation =

1. Download the plugin ZIP file
2. Log in to your WordPress admin panel
3. Go to **Plugins > Add New > Upload Plugin**
4. Choose the ZIP file and click **Install Now**
5. Activate the plugin
6. Go to **Settings > AI Comment Guard** to configure

= Configuration =

1. Navigate to **Settings > AI Comment Guard**
2. Select your AI provider (OpenAI, Anthropic, or OpenRouter)
3. Enter your API key
4. Test the connection to ensure it's working
5. Configure your thresholds and preferences
6. Save your settings

== Frequently Asked Questions ==

= Do I need an API key? =

Yes, you need an API key from one of the supported AI providers:
- OpenAI: https://platform.openai.com/api-keys
- Anthropic: https://console.anthropic.com/
- OpenRouter: https://openrouter.ai/

= How much does it cost to use? =

The plugin itself is free. However, you'll need to pay for API usage with your chosen provider. Costs vary by provider and usage volume. Most providers offer free tiers or credits to get started.

= Can I customize how the AI analyzes comments? =

Yes! The plugin includes customizable prompts where you can define exactly how you want the AI to evaluate comments. You can set different criteria for spam, approval, and rejection.

= Is my data secure? =

Absolutely. Your API keys are stored securely in your WordPress database. Comment data is only sent to your chosen AI provider for analysis and is not stored elsewhere.

= Can I review AI decisions? =

Yes, the plugin includes comprehensive logging (optional) that shows you exactly what the AI decided and why, including confidence scores for each decision.

= Does it work with all themes? =

Yes, AI Comment Guard works with any WordPress theme as it operates at the comment processing level, before comments are displayed.

= Can I disable automatic processing? =

Yes, you can disable automatic processing and use the plugin for manual moderation assistance instead.

= What languages does it support? =

The plugin interface is in English by default but is fully translatable. The AI can analyze comments in multiple languages depending on your chosen provider's capabilities.

= Will it slow down my site? =

No, comment processing happens asynchronously and doesn't affect page load times for your visitors.

= Can I use multiple API providers? =

Currently, you can configure one provider at a time, but you can switch between providers at any time through the settings.

== Screenshots ==

1. **Settings Page** – Configure your AI provider, API key, thresholds, and logging options.
2. **Prompt Customization** – Define and adjust the system prompt to control AI moderation behavior.
3. **Moderation Logs** – Review processed comments, AI analysis results, and final moderation actions.

== Changelog ==

= 1.1.0 =
* Added AJAX connection testing for AI providers
* Enhanced settings interface with real-time connection validation
* Improved settings sanitization with filter_var for boolean fields
* Fixed custom system message handling to allow empty strings
* Enhanced security with improved parameter sanitization in database queries
* Updated logs table headers for better clarity
* Refactored cleanup methods for production compliance
* Updated translation files with new strings

= 1.0.0 =
* Initial release of AI Comment Guard
* Multi-provider AI support (OpenAI, Anthropic, OpenRouter)
* Automatic comment moderation with configurable thresholds
* Smart classification: auto-approve, reject, hold, or mark as spam
* Customizable AI prompts for tailored analysis
* Comprehensive logging system with detailed statistics
* Professional admin interface with intuitive configuration
* Secure API integration with encrypted token storage
* Complete internationalization support
* Modern PHP architecture with PSR-4 autoloading
* WordPress coding standards compliance
* Comprehensive security measures (nonces, sanitization, validation)
* Performance optimized with database indexing
* Automatic cleanup and maintenance features

== Upgrade Notice ==

= 1.0.0 =
Welcome to AI Comment Guard! This is the initial stable release with full functionality for AI-powered comment moderation.

== Additional Information ==

= Support =

For support, feature requests, or bug reports, please visit:
* [LinkedIn Profile](https://www.linkedin.com/in/tudor-eusebiu-constantin/)
* [GitHub Repository](https://github.com/tudor-constantin/ai-comment-guard)

= Contributing =

We welcome contributions! If you'd like to contribute to the development of AI Comment Guard:
* Report bugs or suggest features through the support forum
* Submit pull requests on GitHub
* Help translate the plugin to your language

= Credits =

* Developed by Tudor Constantin
* Thanks to the WordPress community for feedback and support
* Icons and graphics from WordPress Dashicons

= License =

AI Comment Guard is licensed under the GPL v2 or later.

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
