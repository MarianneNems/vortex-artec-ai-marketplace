#!/usr/bin/env python3
"""
THORIUS AGENT
Platform Guide, Chat Assistant & Security Monitor
Hardware: CPU-Optimized
"""

import argparse
import gradio as gr
import json
import logging
import requests
import time
from datetime import datetime
from typing import Dict, List, Optional
import re
import threading
import queue

# Set up logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

class ThoriusAgent:
    def __init__(self):
        """Initialize THORIUS Agent"""
        self.conversation_history = []
        self.security_alerts = []
        self.platform_stats = {
            "active_users": 0,
            "security_incidents": 0,
            "chat_sessions": 0,
            "last_update": datetime.now().isoformat()
        }
        
        # Chat knowledge base
        self.knowledge_base = self.load_knowledge_base()
        
        # Security monitoring
        self.security_patterns = self.load_security_patterns()
        
        logger.info("🛡️ THORIUS Agent initialized - Platform Guide & Security Monitor")
    
    def load_knowledge_base(self) -> Dict:
        """Load THORIUS knowledge base for platform guidance"""
        
        return {
            "platform_features": {
                "ai_agents": {
                    "huraii": "GPU-powered generative AI for creating stunning artwork",
                    "cloe": "Market analysis and collector matching expert", 
                    "horace": "Content optimization and SEO specialist",
                    "archer": "Master orchestrator coordinating all AI agents"
                },
                "marketplace": {
                    "artist_journey": "Complete artist onboarding from registration to NFT sales",
                    "subscription_plans": "Starter ($19.99), Pro ($39.99), Studio ($99.99)",
                    "payment_system": "1:1 USD to TOLA token conversion",
                    "blockchain": "Solana integration for NFT minting and trading"
                },
                "security": {
                    "encryption": "AES-256-GCM for all sensitive data",
                    "authentication": "Multi-factor authentication required",
                    "vault_protection": "All AI algorithms secured in RunPod vault"
                }
            },
            
            "common_questions": {
                "how_to_start": "Welcome! Start by selecting a subscription plan, then complete your artist profile and upload your first seed artwork. I'll guide you through each step!",
                "payment_help": "We accept all major payment methods. Your USD payments are automatically converted to TOLA tokens at 1:1 ratio for marketplace transactions.",
                "ai_agents_help": "Our AI agents work together: HURAII creates art on GPU, while CLOE, HORACE, and I run on CPU to help with analysis, content, and guidance.",
                "security_concern": "Your data is protected with military-grade encryption. All AI algorithms are secured in our vault and never exposed.",
                "nft_minting": "Once your artwork is approved, we'll help you mint it as an NFT on Solana blockchain. The process is automated and user-friendly."
            },
            
            "platform_rules": [
                "Respect all community members and their artwork",
                "No copyright infringement - only original or properly licensed content",
                "No offensive, harmful, or inappropriate content",
                "Follow marketplace terms of service",
                "Report any suspicious activity immediately"
            ]
        }
    
    def load_security_patterns(self) -> List[Dict]:
        """Load security monitoring patterns"""
        
        return [
            {
                "name": "sql_injection",
                "pattern": r"(union|select|insert|delete|drop|exec|script)",
                "severity": "high",
                "description": "Potential SQL injection attempt"
            },
            {
                "name": "xss_attempt", 
                "pattern": r"(<script|javascript:|on\w+\s*=)",
                "severity": "high",
                "description": "Potential XSS attack"
            },
            {
                "name": "suspicious_requests",
                "pattern": r"(admin|config|\.env|password|token)",
                "severity": "medium",
                "description": "Suspicious file or parameter access"
            },
            {
                "name": "rate_limit_breach",
                "pattern": r"rate.limit",
                "severity": "medium", 
                "description": "Rate limit exceeded"
            }
        ]
    
    def chat_response(self, user_message: str, conversation_context: List = None) -> Dict:
        """Generate chat response as platform guide"""
        
        chat_start = datetime.now()
        
        # Clean and analyze user message
        cleaned_message = self.clean_user_input(user_message)
        
        # Security check
        security_check = self.security_scan(cleaned_message)
        if security_check["threat_detected"]:
            return self.handle_security_threat(security_check)
        
        # Generate response
        response = self.generate_guidance_response(cleaned_message, conversation_context)
        
        # Log conversation
        conversation_log = {
            "timestamp": chat_start.isoformat(),
            "user_message": cleaned_message,
            "response": response["message"],
            "response_type": response["type"],
            "security_cleared": True,
            "response_time": (datetime.now() - chat_start).total_seconds()
        }
        
        self.conversation_history.append(conversation_log)
        self.platform_stats["chat_sessions"] += 1
        
        return response
    
    def clean_user_input(self, message: str) -> str:
        """Clean and sanitize user input"""
        
        # Remove potential harmful characters
        cleaned = re.sub(r'[<>"\']', '', message)
        
        # Limit message length
        cleaned = cleaned[:1000]
        
        return cleaned.strip()
    
    def security_scan(self, message: str) -> Dict:
        """Scan message for security threats"""
        
        threats_detected = []
        
        for pattern_info in self.security_patterns:
            if re.search(pattern_info["pattern"], message.lower()):
                threats_detected.append({
                    "type": pattern_info["name"],
                    "severity": pattern_info["severity"],
                    "description": pattern_info["description"]
                })
        
        if threats_detected:
            # Log security incident
            security_incident = {
                "timestamp": datetime.now().isoformat(),
                "message": message,
                "threats": threats_detected,
                "source_ip": "unknown",  # Would get from request in production
                "action": "blocked"
            }
            
            self.security_alerts.append(security_incident)
            self.platform_stats["security_incidents"] += 1
            
            logger.warning(f"🛡️ Security threat detected: {threats_detected}")
        
        return {
            "threat_detected": len(threats_detected) > 0,
            "threats": threats_detected,
            "risk_level": self.calculate_risk_level(threats_detected)
        }
    
    def calculate_risk_level(self, threats: List[Dict]) -> str:
        """Calculate overall risk level from detected threats"""
        
        if not threats:
            return "none"
        
        high_risk = any(t["severity"] == "high" for t in threats)
        
        if high_risk:
            return "high"
        elif len(threats) > 2:
            return "medium"
        else:
            return "low"
    
    def handle_security_threat(self, security_check: Dict) -> Dict:
        """Handle detected security threat"""
        
        risk_level = security_check["risk_level"]
        
        if risk_level == "high":
            message = "🛡️ Security Alert: Your message contains potentially harmful content and has been blocked. Please contact support if you believe this is an error."
        else:
            message = "⚠️ Your message contains suspicious content. Please rephrase and try again."
        
        return {
            "message": message,
            "type": "security_warning",
            "blocked": True,
            "risk_level": risk_level,
            "timestamp": datetime.now().isoformat()
        }
    
    def generate_guidance_response(self, message: str, context: List = None) -> Dict:
        """Generate helpful guidance response"""
        
        message_lower = message.lower()
        
        # Check for common questions
        for question_type, answer in self.knowledge_base["common_questions"].items():
            keywords = question_type.replace("_", " ").split()
            if any(keyword in message_lower for keyword in keywords):
                return {
                    "message": f"📋 {answer}",
                    "type": "guidance",
                    "category": question_type,
                    "helpful_links": self.get_helpful_links(question_type)
                }
        
        # Check for platform feature questions
        if any(word in message_lower for word in ["feature", "how", "what", "can"]):
            return self.explain_platform_features(message_lower)
        
        # Check for help requests
        if any(word in message_lower for word in ["help", "support", "problem", "issue"]):
            return self.provide_support_guidance(message_lower)
        
        # Check for greeting
        if any(word in message_lower for word in ["hello", "hi", "hey", "greetings"]):
            return {
                "message": "👋 Hello! I'm THORIUS, your platform guide and security monitor. I'm here to help you navigate the VORTEX AI Marketplace. What can I assist you with today?",
                "type": "greeting",
                "category": "welcome"
            }
        
        # Default helpful response
        return {
            "message": "🤔 I'm here to help! You can ask me about:\n• Platform features and AI agents\n• Subscription plans and pricing\n• Artist journey and NFT minting\n• Security and safety\n• General marketplace guidance\n\nWhat would you like to know?",
            "type": "default_help",
            "category": "general"
        }
    
    def explain_platform_features(self, message: str) -> Dict:
        """Explain specific platform features"""
        
        if "ai" in message or "agent" in message:
            return {
                "message": "🤖 Our AI Agents:\n• HURAII: GPU-powered art generation\n• CLOE: Market analysis & collector matching\n• HORACE: Content optimization\n• ARCHER: Master orchestrator\n• THORIUS (me): Your guide & security monitor",
                "type": "feature_explanation",
                "category": "ai_agents"
            }
        
        if "payment" in message or "subscription" in message:
            return {
                "message": "💳 Subscription Plans:\n• Starter: $19.99/month\n• Pro: $39.99/month\n• Studio: $99.99/month\n\nAll payments convert to TOLA tokens at 1:1 ratio!",
                "type": "feature_explanation", 
                "category": "pricing"
            }
        
        return {
            "message": "🚀 VORTEX AI Marketplace features include AI-powered art generation, NFT minting, collector matching, and complete artist journey management. What specific feature interests you?",
            "type": "feature_overview",
            "category": "general_features"
        }
    
    def provide_support_guidance(self, message: str) -> Dict:
        """Provide support and troubleshooting guidance"""
        
        if "login" in message or "account" in message:
            return {
                "message": "🔐 Account Issues:\n1. Check your email for verification\n2. Try password reset if needed\n3. Ensure 2FA is properly configured\n4. Contact support if problems persist",
                "type": "support_guidance",
                "category": "account_help"
            }
        
        if "payment" in message or "billing" in message:
            return {
                "message": "💰 Payment Help:\n1. Check your payment method is valid\n2. Verify billing information\n3. TOLA tokens convert 1:1 from USD\n4. Contact billing support for disputes",
                "type": "support_guidance",
                "category": "payment_help"
            }
        
        return {
            "message": "🆘 I'm here to help! Please describe your specific issue and I'll provide targeted guidance. For urgent matters, contact our support team directly.",
            "type": "support_general",
            "category": "general_support"
        }
    
    def get_helpful_links(self, question_type: str) -> List[str]:
        """Get helpful links based on question type"""
        
        link_map = {
            "how_to_start": ["/artist-onboarding", "/subscription-plans"],
            "payment_help": ["/billing", "/tola-tokens"],
            "ai_agents_help": ["/ai-features", "/agent-documentation"],
            "security_concern": ["/security", "/privacy-policy"],
            "nft_minting": ["/nft-guide", "/blockchain-info"]
        }
        
        return link_map.get(question_type, ["/help", "/documentation"])
    
    def get_platform_status(self) -> Dict:
        """Get current platform status and statistics"""
        
        return {
            "status": "operational",
            "uptime": "99.9%",
            "active_users": self.platform_stats["active_users"],
            "chat_sessions": self.platform_stats["chat_sessions"],
            "security_incidents": self.platform_stats["security_incidents"],
            "ai_agents_status": {
                "huraii": "GPU processing",
                "cloe": "CPU analyzing", 
                "horace": "CPU optimizing",
                "archer": "CPU orchestrating"
            },
            "last_update": self.platform_stats["last_update"]
        }
    
    def gradio_interface(self):
        """Create Gradio interface for THORIUS chat"""
        
        def chat_with_thorius(message, history):
            """Gradio chat interface"""
            
            if not message.strip():
                return history, ""
            
            # Get response from THORIUS
            response = self.chat_response(message, history)
            
            # Add to chat history
            history.append([message, response["message"]])
            
            return history, ""
        
        def get_platform_stats():
            """Get platform statistics"""
            
            stats = self.get_platform_status()
            stats_text = f"""
🛡️ THORIUS Platform Monitor

📊 Current Status: {stats['status'].upper()}
⏱️ Uptime: {stats['uptime']}
👥 Active Users: {stats['active_users']}
💬 Chat Sessions: {stats['chat_sessions']}
🚨 Security Incidents: {stats['security_incidents']}

🤖 AI Agents Status:
• HURAII: {stats['ai_agents_status']['huraii']}
• CLOE: {stats['ai_agents_status']['cloe']}
• HORACE: {stats['ai_agents_status']['horace']}
• ARCHER: {stats['ai_agents_status']['archer']}

Last Update: {stats['last_update']}
            """
            
            return stats_text
        
        # Create Gradio interface
        with gr.Blocks(title="THORIUS - Platform Guide") as interface:
            
            gr.Markdown("# 🛡️ THORIUS Agent - Platform Guide & Security Monitor")
            gr.Markdown("Your friendly AI guide for the VORTEX AI Marketplace")
            
            with gr.Tab("💬 Platform Chat"):
                chatbot = gr.Chatbot(label="Chat with THORIUS")
                msg = gr.Textbox(label="Your Message", placeholder="Ask me anything about the platform...")
                clear = gr.Button("Clear Chat")
                
                msg.submit(chat_with_thorius, [msg, chatbot], [chatbot, msg])
                clear.click(lambda: ([], ""), outputs=[chatbot, msg])
            
            with gr.Tab("📊 Platform Status"):
                status_display = gr.Textbox(label="Platform Status", lines=15)
                refresh_btn = gr.Button("Refresh Status")
                
                refresh_btn.click(get_platform_stats, outputs=status_display)
                
                # Auto-refresh on load
                interface.load(get_platform_stats, outputs=status_display)
        
        return interface

def main():
    """Main function to start THORIUS Agent"""
    
    parser = argparse.ArgumentParser(description="THORIUS Agent - Platform Guide & Security")
    parser.add_argument("--port", type=int, default=8002, help="Port to run Gradio interface")
    parser.add_argument("--host", type=str, default="0.0.0.0", help="Host to bind to")
    
    args = parser.parse_args()
    
    # Initialize THORIUS
    thorius = ThoriusAgent()
    
    logger.info("🛡️ THORIUS Agent Status:")
    logger.info("   Platform Guide: ACTIVE")
    logger.info("   Security Monitor: ACTIVE") 
    logger.info("   Knowledge Base: LOADED")
    logger.info("   Chat Interface: READY")
    
    # Create and launch Gradio interface
    interface = thorius.gradio_interface()
    interface.launch(
        server_name=args.host,
        server_port=args.port,
        share=False,
        debug=False
    )

if __name__ == "__main__":
    main() 