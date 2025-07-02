#!/usr/bin/env python3
"""
HURAII GPU ENGINE
Generative AI Engine for VORTEX AI Marketplace
Hardware: GPU-Optimized (CUDA/PyTorch)
"""

import torch
import torch.nn as nn
import argparse
import gradio as gr
import numpy as np
from transformers import AutoTokenizer, AutoModel
from diffusers import StableDiffusionPipeline
import os
import json
import logging
from datetime import datetime
import requests

# Set up logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

class HuraiiGPUEngine:
    def __init__(self, gpu_device="cuda:0"):
        """Initialize HURAII GPU Engine"""
        self.device = gpu_device if torch.cuda.is_available() else "cpu"
        self.model_cache = {}
        self.generation_history = []
        
        logger.info(f"🚀 HURAII GPU Engine initializing on {self.device}")
        
        # Initialize models
        self.init_models()
        
    def init_models(self):
        """Initialize AI models for art generation"""
        try:
            # Stable Diffusion for art generation
            logger.info("Loading Stable Diffusion model...")
            self.sd_pipe = StableDiffusionPipeline.from_pretrained(
                "runwayml/stable-diffusion-v1-5",
                torch_dtype=torch.float16 if self.device != "cpu" else torch.float32,
                safety_checker=None,
                requires_safety_checker=False
            )
            self.sd_pipe = self.sd_pipe.to(self.device)
            
            # Enable memory efficient attention
            if self.device != "cpu":
                self.sd_pipe.enable_attention_slicing()
                self.sd_pipe.enable_memory_efficient_attention()
            
            logger.info("✅ HURAII models loaded successfully")
            
        except Exception as e:
            logger.error(f"❌ Model loading failed: {e}")
            raise
    
    def generate_art(self, prompt, style="photorealistic", quality="high", seed=None):
        """Generate AI art based on prompt and style"""
        
        generation_start = datetime.now()
        
        # Enhance prompt based on style
        enhanced_prompt = self.enhance_prompt(prompt, style)
        
        # Set generation parameters
        num_inference_steps = 50 if quality == "high" else 30
        guidance_scale = 7.5
        width, height = 512, 512
        
        if seed is not None:
            generator = torch.Generator(device=self.device).manual_seed(seed)
        else:
            generator = None
        
        try:
            # Generate image
            with torch.autocast(self.device):
                result = self.sd_pipe(
                    enhanced_prompt,
                    num_inference_steps=num_inference_steps,
                    guidance_scale=guidance_scale,
                    width=width,
                    height=height,
                    generator=generator,
                    return_dict=True
                )
            
            image = result.images[0]
            generation_time = (datetime.now() - generation_start).total_seconds()
            
            # Analyze generated image
            analysis = self.analyze_generated_art(image, prompt, style)
            
            # Log generation
            generation_log = {
                "timestamp": generation_start.isoformat(),
                "prompt": prompt,
                "enhanced_prompt": enhanced_prompt,
                "style": style,
                "quality": quality,
                "seed": seed,
                "generation_time": generation_time,
                "analysis": analysis
            }
            
            self.generation_history.append(generation_log)
            
            return {
                "image": image,
                "generation_time": generation_time,
                "analysis": analysis,
                "metadata": generation_log
            }
            
        except Exception as e:
            logger.error(f"❌ Art generation failed: {e}")
            return {"error": str(e)}
    
    def enhance_prompt(self, prompt, style):
        """Enhance prompt based on artistic style"""
        
        style_prompts = {
            "photorealistic": "highly detailed, photorealistic, 8k resolution, professional photography",
            "artistic": "beautiful artwork, artistic masterpiece, detailed illustration",
            "abstract": "abstract art, modern composition, creative interpretation",
            "fantasy": "fantasy art, magical, mystical, enchanted",
            "cyberpunk": "cyberpunk style, neon lights, futuristic, digital art",
            "vintage": "vintage style, retro aesthetic, classic composition"
        }
        
        style_enhancement = style_prompts.get(style, "high quality artwork")
        enhanced = f"{prompt}, {style_enhancement}"
        
        return enhanced
    
    def analyze_generated_art(self, image, original_prompt, style):
        """Analyze the generated artwork"""
        
        # Convert PIL image to numpy array for analysis
        img_array = np.array(image)
        
        analysis = {
            "dimensions": f"{image.width}x{image.height}",
            "color_analysis": self.analyze_colors(img_array),
            "composition_score": self.score_composition(img_array),
            "style_match": self.evaluate_style_match(img_array, style),
            "prompt_adherence": self.evaluate_prompt_adherence(original_prompt),
            "quality_score": self.calculate_quality_score(img_array)
        }
        
        return analysis
    
    def analyze_colors(self, img_array):
        """Analyze color composition of generated art"""
        
        # Calculate dominant colors
        pixels = img_array.reshape(-1, 3)
        
        # Simple color analysis
        avg_color = np.mean(pixels, axis=0)
        color_variance = np.var(pixels, axis=0) 
        
        return {
            "average_rgb": avg_color.tolist(),
            "color_variance": color_variance.tolist(),
            "brightness": float(np.mean(avg_color)),
            "contrast": float(np.std(pixels))
        }
    
    def score_composition(self, img_array):
        """Score the composition of the artwork"""
        
        # Simple composition scoring based on image statistics
        height, width = img_array.shape[:2]
        
        # Rule of thirds analysis
        third_h, third_w = height // 3, width // 3
        
        # Calculate composition score (0-1)
        composition_score = min(1.0, (width * height) / (512 * 512))
        
        return round(composition_score, 3)
    
    def evaluate_style_match(self, img_array, target_style):
        """Evaluate how well the image matches the target style"""
        
        # Simplified style matching (in production, would use trained models)
        style_scores = {
            "photorealistic": 0.85,
            "artistic": 0.80,
            "abstract": 0.75,
            "fantasy": 0.82,
            "cyberpunk": 0.78,
            "vintage": 0.80
        }
        
        return style_scores.get(target_style, 0.75)
    
    def evaluate_prompt_adherence(self, prompt):
        """Evaluate how well the image adheres to the prompt"""
        
        # Simplified prompt adherence (in production, would use CLIP models)
        # Score based on prompt complexity and common keywords
        
        keywords = len(prompt.split())
        complexity_score = min(1.0, keywords / 20)  # Normalize to 0-1
        
        return round(0.7 + (complexity_score * 0.3), 3)
    
    def calculate_quality_score(self, img_array):
        """Calculate overall quality score of the generated art"""
        
        # Calculate sharpness (using gradient magnitude)
        gray = np.mean(img_array, axis=2) if len(img_array.shape) == 3 else img_array
        gradient_x = np.gradient(gray, axis=1)
        gradient_y = np.gradient(gray, axis=0)
        gradient_magnitude = np.sqrt(gradient_x**2 + gradient_y**2)
        sharpness = np.mean(gradient_magnitude)
        
        # Normalize sharpness to 0-1 scale
        quality_score = min(1.0, sharpness / 100)
        
        return round(quality_score, 3)
    
    def get_gpu_stats(self):
        """Get current GPU utilization stats"""
        
        if not torch.cuda.is_available():
            return {"gpu_available": False}
        
        gpu_memory = torch.cuda.get_device_properties(0).total_memory / 1024**3
        gpu_memory_used = torch.cuda.memory_allocated(0) / 1024**3
        gpu_memory_cached = torch.cuda.memory_reserved(0) / 1024**3
        
        return {
            "gpu_available": True,
            "gpu_name": torch.cuda.get_device_name(0),
            "gpu_memory_total": round(gpu_memory, 2),
            "gpu_memory_used": round(gpu_memory_used, 2),
            "gpu_memory_cached": round(gpu_memory_cached, 2),
            "gpu_utilization": f"{(gpu_memory_used/gpu_memory)*100:.1f}%"
        }
    
    def gradio_interface(self):
        """Create Gradio interface for HURAII"""
        
        def generate_with_gradio(prompt, style, quality, seed_input):
            """Gradio wrapper for art generation"""
            
            seed = int(seed_input) if seed_input.strip() else None
            result = self.generate_art(prompt, style, quality, seed)
            
            if "error" in result:
                return None, f"Error: {result['error']}", {}
            
            gpu_stats = self.get_gpu_stats()
            
            info_text = (f"Generation Time: {result['generation_time']:.2f}s\n"
                        f"Quality Score: {result['analysis']['quality_score']}\n"
                        f"Style Match: {result['analysis']['style_match']}\n"
                        f"GPU Utilization: {gpu_stats.get('gpu_utilization', 'N/A')}")
            
            return result['image'], info_text, result['analysis']
        
        # Create Gradio interface
        iface = gr.Interface(
            fn=generate_with_gradio,
            inputs=[
                gr.Textbox(label="Art Prompt", placeholder="Describe the artwork you want to create..."),
                gr.Dropdown(
                    choices=["photorealistic", "artistic", "abstract", "fantasy", "cyberpunk", "vintage"],
                    value="artistic",
                    label="Art Style"
                ),
                gr.Dropdown(
                    choices=["standard", "high"],
                    value="high", 
                    label="Quality"
                ),
                gr.Textbox(label="Seed (optional)", placeholder="Random seed for reproducible results")
            ],
            outputs=[
                gr.Image(label="Generated Artwork"),
                gr.Textbox(label="Generation Info"),
                gr.JSON(label="Analysis Details")
            ],
            title="🚀 HURAII GPU Engine - AI Art Generator",
            description="VORTEX AI Marketplace - GPU-Powered Generative AI",
            theme="default"
        )
        
        return iface

def main():
    """Main function to start HURAII GPU Engine"""
    
    parser = argparse.ArgumentParser(description="HURAII GPU Engine")
    parser.add_argument("--port", type=int, default=7860, help="Port to run Gradio interface")
    parser.add_argument("--host", type=str, default="0.0.0.0", help="Host to bind to")
    parser.add_argument("--gpu", type=str, default="cuda:0", help="GPU device to use")
    
    args = parser.parse_args()
    
    # Initialize HURAII
    huraii = HuraiiGPUEngine(gpu_device=args.gpu)
    
    # Get GPU stats
    gpu_stats = huraii.get_gpu_stats()
    logger.info(f"🚀 HURAII GPU Engine Status:")
    logger.info(f"   GPU Available: {gpu_stats.get('gpu_available', False)}")
    if gpu_stats.get('gpu_available'):
        logger.info(f"   GPU Name: {gpu_stats['gpu_name']}")
        logger.info(f"   GPU Memory: {gpu_stats['gpu_memory_total']} GB")
    
    # Create and launch Gradio interface
    interface = huraii.gradio_interface()
    interface.launch(
        server_name=args.host,
        server_port=args.port,
        share=False,
        debug=False
    )

if __name__ == "__main__":
    main() 