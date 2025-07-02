<?php
/**
 * Strategic Business Assessment Template
 * 
 * @package Vortex_AI_Marketplace
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

?>

<div class="quiz-container vortex-strategic-assessment" id="vortex-quiz-container">
    <div class="quiz-header">
        <h2><?php echo esc_html($quiz_atts['title']); ?></h2>
        <p class="quiz-subtitle"><?php echo esc_html($quiz_atts['subtitle']); ?></p>
        <div class="quiz-description">
            <p>Complete this strategic assessment to receive your personalized 30-day milestone plan crafted by Horace, your AI business strategist.</p>
        </div>
    </div>

    <form id="vortex-strategic-quiz-form" class="strategic-quiz-form" method="post">
        <!-- Personal Information Section -->
        <div class="form-section personal-info-section">
            <div class="personal-fields">
                <div class="input-group">
                    <input type="date" id="dob" name="dob" required>
                </div>
                
                <div class="input-group">
                    <input type="text" id="pob" name="pob" placeholder="City, Country" required>
                </div>
                
                <div class="input-group">
                    <input type="time" id="tob" name="tob" required>
                </div>
            </div>
        </div>

        <!-- Strategic Questions Section -->
        <div class="form-section questions-section">
            <h3>📊 Strategic Business Assessment</h3>
            <p>Select the option that best aligns with your business goals and add any additional thoughts.</p>

            <!-- Question 1 -->
            <div class="question-block" data-question="q1">
                <div class="question-content">
                    <h4>1. What is your most vital revenue target in the next 30 days?</h4>
                    <div class="question-options">
                        <label class="option-label">
                            <input type="radio" name="q1" value="a" required>
                            <span>$500 - $2,000 from new client acquisitions and direct sales</span>
                        </label>
                        <label class="option-label">
                            <input type="radio" name="q1" value="b" required>
                            <span>$2,000 - $5,000 from premium services and high-value offerings</span>
                        </label>
                        <label class="option-label">
                            <input type="radio" name="q1" value="c" required>
                            <span>$5,000 - $15,000 from strategic partnerships and collaborations</span>
                        </label>
                        <label class="option-label">
                            <input type="radio" name="q1" value="d" required>
                            <span>$15,000+ from enterprise contracts and major commissions</span>
                        </label>
                    </div>
                    <div class="additional-thoughts">
                        <textarea name="q1_notes" placeholder="Additional thoughts on your revenue target..."></textarea>
                    </div>
                </div>
                <div class="confirmation-section">
                    <label class="confirm-label">
                        <input type="checkbox" class="question-confirm" required>
                        I confirm this represents my vital revenue target
                    </label>
                </div>
            </div>

            <!-- Question 2 -->
            <div class="question-block" data-question="q2">
                <div class="question-content">
                    <h4>2. Which two marketing channels will you champion this month?</h4>
                    <div class="question-options">
                        <label class="option-label">
                            <input type="radio" name="q2" value="a" required>
                            <span>Social media content creation + Email marketing campaigns</span>
                        </label>
                        <label class="option-label">
                            <input type="radio" name="q2" value="b" required>
                            <span>SEO-optimized content + Professional networking events</span>
                        </label>
                        <label class="option-label">
                            <input type="radio" name="q2" value="c" required>
                            <span>Paid advertising campaigns + Referral partnership programs</span>
                        </label>
                        <label class="option-label">
                            <input type="radio" name="q2" value="d" required>
                            <span>Industry publications + Speaking engagements and workshops</span>
                        </label>
                    </div>
                    <div class="additional-thoughts">
                        <textarea name="q2_notes" placeholder="Additional thoughts on your marketing channels..."></textarea>
                    </div>
                </div>
                <div class="confirmation-section">
                    <label class="confirm-label">
                        <input type="checkbox" class="question-confirm" required>
                        I confirm these are my champion marketing channels
                    </label>
                </div>
            </div>

            <!-- Question 3 -->
            <div class="question-block" data-question="q3">
                <div class="question-content">
                    <h4>3. What is your ideal price bracket by month's end?</h4>
                    <div class="question-options">
                        <label class="option-label">
                            <input type="radio" name="q3" value="a" required>
                            <span>Accessible tier: $50-$200 per project to build client base</span>
                        </label>
                        <label class="option-label">
                            <input type="radio" name="q3" value="b" required>
                            <span>Professional tier: $200-$800 per project for quality work</span>
                        </label>
                        <label class="option-label">
                            <input type="radio" name="q3" value="c" required>
                            <span>Premium tier: $800-$2,500 per project for specialized expertise</span>
                        </label>
                        <label class="option-label">
                            <input type="radio" name="q3" value="d" required>
                            <span>Luxury tier: $2,500+ per project for exclusive, high-impact work</span>
                        </label>
                    </div>
                    <div class="additional-thoughts">
                        <textarea name="q3_notes" placeholder="Additional thoughts on your ideal price bracket..."></textarea>
                    </div>
                </div>
                <div class="confirmation-section">
                    <label class="confirm-label">
                        <input type="checkbox" class="question-confirm" required>
                        I confirm this is my ideal price bracket
                    </label>
                </div>
            </div>

            <!-- Question 4 -->
            <div class="question-block" data-question="q4">
                <div class="question-content">
                    <h4>4. What collaboration type will expand your influence most powerfully?</h4>
                    <div class="question-options">
                        <label class="option-label">
                            <input type="radio" name="q4" value="a" required>
                            <span>Co-creation partnerships with other artists and creative professionals</span>
                        </label>
                        <label class="option-label">
                            <input type="radio" name="q4" value="b" required>
                            <span>Strategic alliances with established brands and thought leaders</span>
                        </label>
                        <label class="option-label">
                            <input type="radio" name="q4" value="c" required>
                            <span>Mentorship roles and educational partnerships with institutions</span>
                        </label>
                        <label class="option-label">
                            <input type="radio" name="q4" value="d" required>
                            <span>Cross-industry innovation projects and media collaborations</span>
                        </label>
                    </div>
                    <div class="additional-thoughts">
                        <textarea name="q4_notes" placeholder="Additional thoughts on expanding your influence..."></textarea>
                    </div>
                </div>
                <div class="confirmation-section">
                    <label class="confirm-label">
                        <input type="checkbox" class="question-confirm" required>
                        I confirm this collaboration type will expand my influence
                    </label>
                </div>
            </div>

            <!-- Question 5 -->
            <div class="question-block" data-question="q5">
                <div class="question-content">
                    <h4>5. What is your unique signature or value proposition?</h4>
                    <div class="question-options">
                        <label class="option-label">
                            <input type="radio" name="q5" value="a" required>
                            <span>Distinctive creative style and artistic vision that sets me apart</span>
                        </label>
                        <label class="option-label">
                            <input type="radio" name="q5" value="b" required>
                            <span>Technical expertise and innovative problem-solving approach</span>
                        </label>
                        <label class="option-label">
                            <input type="radio" name="q5" value="c" required>
                            <span>Deep market understanding and client relationship excellence</span>
                        </label>
                        <label class="option-label">
                            <input type="radio" name="q5" value="d" required>
                            <span>Comprehensive service delivery and exceptional quality consistency</span>
                        </label>
                    </div>
                    <div class="additional-thoughts">
                        <textarea name="q5_notes" placeholder="Additional thoughts on your unique value proposition..."></textarea>
                    </div>
                </div>
                <div class="confirmation-section">
                    <label class="confirm-label">
                        <input type="checkbox" class="question-confirm" required>
                        I confirm this represents my unique signature value
                    </label>
                </div>
            </div>

            <!-- Question 6 -->
            <div class="question-block" data-question="q6">
                <div class="question-content">
                    <h4>6. What resources and weekly hours can you commit to growth?</h4>
                    <div class="question-options">
                        <label class="option-label">
                            <input type="radio" name="q6" value="a" required>
                            <span>5-10 hours/week with limited budget for tools and resources</span>
                        </label>
                        <label class="option-label">
                            <input type="radio" name="q6" value="b" required>
                            <span>10-20 hours/week with moderate investment in marketing and development</span>
                        </label>
                        <label class="option-label">
                            <input type="radio" name="q6" value="c" required>
                            <span>20-30 hours/week with significant budget for growth initiatives</span>
                        </label>
                        <label class="option-label">
                            <input type="radio" name="q6" value="d" required>
                            <span>30+ hours/week with substantial investment in business expansion</span>
                        </label>
                    </div>
                    <div class="additional-thoughts">
                        <textarea name="q6_notes" placeholder="Additional thoughts on your resource commitment..."></textarea>
                    </div>
                </div>
                <div class="confirmation-section">
                    <label class="confirm-label">
                        <input type="checkbox" class="question-confirm" required>
                        I confirm this reflects my realistic commitment level
                    </label>
                </div>
            </div>

            <!-- Question 7 -->
            <div class="question-block" data-question="q7">
                <div class="question-content">
                    <h4>7. What are your preferred revenue streams?</h4>
                    <div class="question-options">
                        <label class="option-label">
                            <input type="radio" name="q7" value="a" required>
                            <span>Direct commissions and custom project work</span>
                        </label>
                        <label class="option-label">
                            <input type="radio" name="q7" value="b" required>
                            <span>Subscription memberships and ongoing service contracts</span>
                        </label>
                        <label class="option-label">
                            <input type="radio" name="q7" value="c" required>
                            <span>Product sales, licensing, and intellectual property royalties</span>
                        </label>
                        <label class="option-label">
                            <input type="radio" name="q7" value="d" required>
                            <span>Teaching, workshops, consulting, and educational programs</span>
                        </label>
                    </div>
                    <div class="additional-thoughts">
                        <textarea name="q7_notes" placeholder="Additional thoughts on your revenue preferences..."></textarea>
                    </div>
                </div>
                <div class="confirmation-section">
                    <label class="confirm-label">
                        <input type="checkbox" class="question-confirm" required>
                        I confirm these are my preferred revenue streams
                    </label>
                </div>
            </div>

            <!-- Question 8 -->
            <div class="question-block" data-question="q8">
                <div class="question-content">
                    <h4>8. What are your previous marketing wins and lessons learned?</h4>
                    <div class="question-options">
                        <label class="option-label">
                            <input type="radio" name="q8" value="a" required>
                            <span>Social media success with strong engagement and community building</span>
                        </label>
                        <label class="option-label">
                            <input type="radio" name="q8" value="b" required>
                            <span>Word-of-mouth referrals and networking have been most effective</span>
                        </label>
                        <label class="option-label">
                            <input type="radio" name="q8" value="c" required>
                            <span>Content marketing and thought leadership have driven results</span>
                        </label>
                        <label class="option-label">
                            <input type="radio" name="q8" value="d" required>
                            <span>I'm still learning what works - open to testing new approaches</span>
                        </label>
                    </div>
                    <div class="additional-thoughts">
                        <textarea name="q8_notes" placeholder="Additional thoughts on your marketing experience..."></textarea>
                    </div>
                </div>
                <div class="confirmation-section">
                    <label class="confirm-label">
                        <input type="checkbox" class="question-confirm" required>
                        I confirm this reflects my marketing experience and lessons
                    </label>
                </div>
            </div>

            <!-- Question 9 -->
            <div class="question-block" data-question="q9">
                <div class="question-content">
                    <h4>9. What community or partnership aspirations do you have?</h4>
                    <div class="question-options">
                        <label class="option-label">
                            <input type="radio" name="q9" value="a" required>
                            <span>Building a vibrant creative community around my work and values</span>
                        </label>
                        <label class="option-label">
                            <input type="radio" name="q9" value="b" required>
                            <span>Establishing strategic partnerships with complementary businesses</span>
                        </label>
                        <label class="option-label">
                            <input type="radio" name="q9" value="c" required>
                            <span>Joining professional associations and industry leadership groups</span>
                        </label>
                        <label class="option-label">
                            <input type="radio" name="q9" value="d" required>
                            <span>Creating collaborative networks for mutual growth and support</span>
                        </label>
                    </div>
                    <div class="additional-thoughts">
                        <textarea name="q9_notes" placeholder="Additional thoughts on your community and partnership goals..."></textarea>
                    </div>
                </div>
                <div class="confirmation-section">
                    <label class="confirm-label">
                        <input type="checkbox" class="question-confirm" required>
                        I confirm these aspirations align with my vision
                    </label>
                </div>
            </div>

            <!-- Question 10 -->
            <div class="question-block" data-question="q10">
                <div class="question-content">
                    <h4>10. What is your biggest entrepreneurial obstacle today?</h4>
                    <div class="question-options">
                        <label class="option-label">
                            <input type="radio" name="q10" value="a" required>
                            <span>Finding consistent clients and generating steady income</span>
                        </label>
                        <label class="option-label">
                            <input type="radio" name="q10" value="b" required>
                            <span>Managing time between creative work and business operations</span>
                        </label>
                        <label class="option-label">
                            <input type="radio" name="q10" value="c" required>
                            <span>Pricing my work appropriately and communicating value</span>
                        </label>
                        <label class="option-label">
                            <input type="radio" name="q10" value="d" required>
                            <span>Scaling my business without losing quality or personal touch</span>
                        </label>
                    </div>
                    <div class="additional-thoughts">
                        <textarea name="q10_notes" placeholder="Additional thoughts on your biggest challenge..."></textarea>
                    </div>
                </div>
                <div class="confirmation-section">
                    <label class="confirm-label">
                        <input type="checkbox" class="question-confirm" required>
                        I confirm this represents my biggest current obstacle
                    </label>
                </div>
            </div>

            <!-- Question 11 -->
            <div class="question-block" data-question="q11">
                <div class="question-content">
                    <h4>11. What essential support do you need most right now?</h4>
                    <div class="question-options">
                        <label class="option-label">
                            <input type="radio" name="q11" value="a" required>
                            <span>Technical support with tools, systems, and digital infrastructure</span>
                        </label>
                        <label class="option-label">
                            <input type="radio" name="q11" value="b" required>
                            <span>Emotional support and encouragement during challenging times</span>
                        </label>
                        <label class="option-label">
                            <input type="radio" name="q11" value="c" required>
                            <span>Educational resources and skill development opportunities</span>
                        </label>
                        <label class="option-label">
                            <input type="radio" name="q11" value="d" required>
                            <span>Strategic guidance and mentorship from experienced professionals</span>
                        </label>
                    </div>
                    <div class="additional-thoughts">
                        <textarea name="q11_notes" placeholder="Additional thoughts on the support you need..."></textarea>
                    </div>
                </div>
                <div class="confirmation-section">
                    <label class="confirm-label">
                        <input type="checkbox" class="question-confirm" required>
                        I confirm this represents my most essential support need
                    </label>
                </div>
            </div>

            <!-- Question 12 -->
            <div class="question-block" data-question="q12">
                <div class="question-content">
                    <h4>12. What is one personal ambition you hold for the year ahead?</h4>
                    <div class="question-options">
                        <label class="option-label">
                            <input type="radio" name="q12" value="a" required>
                            <span>Achieving financial independence and sustainable creative freedom</span>
                        </label>
                        <label class="option-label">
                            <input type="radio" name="q12" value="b" required>
                            <span>Making a meaningful impact through my work and artistic vision</span>
                        </label>
                        <label class="option-label">
                            <input type="radio" name="q12" value="c" required>
                            <span>Building a lasting legacy and inspiring the next generation</span>
                        </label>
                        <label class="option-label">
                            <input type="radio" name="q12" value="d" required>
                            <span>Finding perfect balance between professional success and personal fulfillment</span>
                        </label>
                    </div>
                    <div class="additional-thoughts">
                        <textarea name="q12_notes" placeholder="Additional thoughts on your personal ambition..."></textarea>
                    </div>
                </div>
                <div class="confirmation-section">
                    <label class="confirm-label">
                        <input type="checkbox" class="question-confirm" required>
                        I confirm this represents my deepest personal ambition
                    </label>
                </div>
            </div>
        </div>

        <!-- Submit Section -->
        <div class="form-section submit-section">
            <div class="submit-info">
                <h3>🚀 Ready for Your Strategic Plan?</h3>
                <p>Once submitted, Horace will analyze your responses and create your personalized 30-day milestone plan. You'll receive:</p>
                <ul>
                    <li>📋 Comprehensive milestone roadmap tailored to your strategic profile</li>
                    <li>📧 Daily coaching emails at 8:00 AM for 30 days</li>
                    <li>🎯 Strategic action steps and progress tracking</li>
                    <li>📈 Weekly review checkpoints and optimization tips</li>
                </ul>
            </div>
            
            <div class="submit-button-container">
                <button type="submit" id="submit-quiz-btn" class="quiz-submit-btn disabled" disabled>
                    <span class="btn-text">Complete Assessment</span>
                    <span class="btn-icon">📊</span>
                </button>
            </div>
            
            <div class="submit-disclaimer">
                <p><small>Your strategic assessment is confidential and will only be used to create your personalized milestone plan. By submitting, you agree to receive daily coaching emails for 30 days.</small></p>
            </div>
        </div>

        <!-- Hidden fields -->
        <input type="hidden" name="user_id" value="<?php echo esc_attr($current_user_id); ?>">
        <input type="hidden" name="redirect_url" value="<?php echo esc_url($quiz_atts['redirect_url']); ?>">
    </form>
</div>

<style>
.vortex-strategic-assessment {
    max-width: 900px;
    margin: 0 auto;
    padding: 20px;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 15px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}

.quiz-header {
    text-align: center;
    margin-bottom: 40px;
    padding: 25px 20px;
    background: linear-gradient(135deg, #007cba 0%, #005a87 100%);
    color: white;
    border-radius: 12px;
}

.quiz-header h2 {
    font-size: 2.2em;
    margin-bottom: 10px;
    font-weight: 600;
}

.quiz-subtitle {
    font-size: 1.2em;
    opacity: 0.9;
    margin-bottom: 18px;
}

.quiz-description p {
    font-size: 1.05em;
    line-height: 1.5;
    margin: 0;
}

.form-section {
    margin: 35px 0;
    padding: 25px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.06);
}

.form-section h3 {
    color: #333;
    font-size: 1.6em;
    margin-bottom: 12px;
    border-bottom: 2px solid #007cba;
    padding-bottom: 8px;
}

.personal-info-section {
    padding: 15px 25px;
    background: #f8f9fa;
    border-left: 4px solid #007cba;
}

.personal-fields {
    display: flex;
    gap: 15px;
    justify-content: center;
}

.personal-fields .input-group {
    margin-bottom: 0;
}

.personal-fields input {
    padding: 8px 12px;
    font-size: 0.9em;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    background: white;
    min-width: 120px;
}

.question-block {
    margin: 25px 0;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 10px;
    border-left: 4px solid #dee2e6;
    transition: all 0.3s ease;
}

.question-block.confirmed {
    border-left-color: #28a745;
    background: #f0f8f0;
}

.question-content h4 {
    color: #333;
    font-size: 1.2em;
    margin-bottom: 18px;
    line-height: 1.4;
}

.question-options {
    margin: 18px 0;
}

.option-label {
    display: block;
    margin: 10px 0;
    padding: 12px;
    background: white;
    border: 2px solid #e9ecef;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.option-label:hover {
    border-color: #007cba;
    background: #f0f8ff;
}

.option-label input[type="radio"] {
    margin-right: 10px;
    transform: scale(1.1);
}

.option-label input[type="radio"]:checked + span {
    font-weight: 600;
    color: #007cba;
}

.additional-thoughts {
    margin: 15px 0;
}

.additional-thoughts textarea {
    width: 100%;
    height: 60px;
    padding: 10px;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    font-family: inherit;
    font-size: 0.9em;
    resize: vertical;
    background: #fefefe;
    box-sizing: border-box;
}

.additional-thoughts textarea:focus {
    outline: none;
    border-color: #007cba;
    box-shadow: 0 0 0 2px rgba(0, 124, 186, 0.1);
}

.confirmation-section {
    margin-top: 20px;
    padding: 15px;
    background: white;
    border-radius: 6px;
    border: 1px solid #e9ecef;
}

.confirm-label {
    display: flex;
    align-items: center;
    font-weight: 500;
    color: #495057;
    cursor: pointer;
}

.confirm-label input[type="checkbox"] {
    margin-right: 10px;
    transform: scale(1.2);
}

.submit-section {
    text-align: center;
}

.submit-info {
    margin-bottom: 25px;
}

.submit-info ul {
    text-align: left;
    max-width: 450px;
    margin: 0 auto;
    padding: 0;
    list-style: none;
}

.submit-info li {
    margin: 8px 0;
    padding: 6px 0;
    border-bottom: 1px solid #eee;
}

.quiz-submit-btn {
    padding: 16px 35px;
    font-size: 1.2em;
    font-weight: 600;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.quiz-submit-btn.disabled {
    background: #6c757d;
    color: white;
    cursor: not-allowed;
    opacity: 0.6;
}

.quiz-submit-btn.enabled {
    background: linear-gradient(135deg, #007cba, #005a87);
    color: white;
    cursor: pointer;
    opacity: 1;
}

.quiz-submit-btn.enabled:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 18px rgba(0, 124, 186, 0.3);
}

.submit-disclaimer {
    margin-top: 18px;
    color: #666;
    font-size: 0.85em;
}

@media (max-width: 768px) {
    .vortex-strategic-assessment {
        padding: 15px;
        margin: 10px;
    }
    
    .quiz-header h2 {
        font-size: 1.8em;
    }
    
    .personal-fields {
        flex-direction: column;
        gap: 10px;
    }
    
    .form-section {
        padding: 18px;
    }
    
    .question-block {
        padding: 16px;
    }
}
</style> 