/**
 * VORTEX Cosmic Business Quiz Enhanced Handler
 *
 * Handles zodiacal personalization, 30-day roadmap generation, and enhanced UX
 */

(function ($) {
  'use strict';

  class VortexCosmicQuiz {
    constructor() {
      this.form = $('#vortex-business-quiz-form');
      this.submitBtn = $('#submit-cosmic-quiz-btn');
      this.progressFill = $('#cosmic-progress-fill');
      this.currentQuestionCounter = $('#current-question');
      this.questionBlocks = $('.cosmic-question');
      this.confirmCheckboxes = $('.question-confirm');
      this.cosmicInputs = $('.cosmic-input');
      this.zodiacPreview = $('#zodiac-preview');
      this.cosmicPreview = $('#cosmic-preview');
      this.progressStars = $('.star');

      this.totalQuestions = this.questionBlocks.length + 3; // Include cosmic profile questions
      this.completedQuestions = 0;
      this.zodiacSign = null;
      this.userResponses = {};

      this.init();
    }

    init() {
      this.bindEvents();
      this.updateProgress();
      this.checkFormValidity();
      this.initCosmicEffects();
    }

    bindEvents() {
      // Handle birth date change for zodiac detection
      $('#dob').on('change', e => {
        this.detectZodiacSign(e.target.value);
        this.checkCosmicProfile();
      });

      // Handle cosmic input changes
      this.cosmicInputs.on('input change', () => {
        this.checkCosmicProfile();
      });

      // Handle radio button changes
      $('input[type="radio"]').on('change', e => {
        this.handleQuestionResponse(e);
      });

      // Handle checkbox changes
      this.confirmCheckboxes.on('change', e => {
        this.handleCheckboxChange(e);
      });

      // Handle form submission
      this.form.on('submit', e => {
        this.handleFormSubmit(e);
      });

      // Add cosmic hover effects
      $('.cosmic-question')
        .on('mouseenter', function () {
          $(this).addClass('cosmic-glow');
        })
        .on('mouseleave', function () {
          $(this).removeClass('cosmic-glow');
        });
    }

    detectZodiacSign(birthDate) {
      if (!birthDate) {
        return;
      }

      const date = new Date(birthDate);
      const month = date.getMonth() + 1;
      const day = date.getDate();

      const zodiacRanges = [
        { sign: 'capricorn', start: [12, 22], end: [1, 19] },
        { sign: 'aquarius', start: [1, 20], end: [2, 18] },
        { sign: 'pisces', start: [2, 19], end: [3, 20] },
        { sign: 'aries', start: [3, 21], end: [4, 19] },
        { sign: 'taurus', start: [4, 20], end: [5, 20] },
        { sign: 'gemini', start: [5, 21], end: [6, 20] },
        { sign: 'cancer', start: [6, 21], end: [7, 22] },
        { sign: 'leo', start: [7, 23], end: [8, 22] },
        { sign: 'virgo', start: [8, 23], end: [9, 22] },
        { sign: 'libra', start: [9, 23], end: [10, 22] },
        { sign: 'scorpio', start: [10, 23], end: [11, 21] },
        { sign: 'sagittarius', start: [11, 22], end: [12, 21] },
      ];

      for (const range of zodiacRanges) {
        if (this.isDateInRange(month, day, range.start, range.end)) {
          this.zodiacSign = range.sign;
          this.displayZodiacPreview(range.sign);
          break;
        }
      }
    }

    isDateInRange(month, day, start, end) {
      const [startMonth, startDay] = start;
      const [endMonth, endDay] = end;

      if (startMonth === endMonth) {
        return month === startMonth && day >= startDay && day <= endDay;
      } else {
        return (month === startMonth && day >= startDay) || (month === endMonth && day <= endDay);
      }
    }

    displayZodiacPreview(sign) {
      if (!vortexQuizData.zodiacSigns[sign]) {
        return;
      }

      const signData = vortexQuizData.zodiacSigns[sign];
      const emoji = this.getZodiacEmoji(sign);

      this.zodiacPreview
        .html(
          `
                <div class="zodiac-card">
                    <div class="zodiac-symbol">${emoji}</div>
                    <div class="zodiac-info">
                        <h4>${signData.name}</h4>
                        <p class="zodiac-element">${signData.element} Sign</p>
                        <div class="zodiac-traits">
                            ${signData.traits.map(trait => `<span class="trait-tag">${trait}</span>`).join('')}
                        </div>
                    </div>
                </div>
            `
        )
        .addClass('show');

      // Add cosmic animation
      setTimeout(() => {
        this.zodiacPreview.addClass('cosmic-pulse');
      }, 500);
    }

    getZodiacEmoji(sign) {
      const emojis = {
        aries: '♈',
        taurus: '♉',
        gemini: '♊',
        cancer: '♋',
        leo: '♌',
        virgo: '♍',
        libra: '♎',
        scorpio: '♏',
        sagittarius: '♐',
        capricorn: '♑',
        aquarius: '♒',
        pisces: '♓',
      };
      return emojis[sign] || '✦';
    }

    checkCosmicProfile() {
      const dob = $('#dob').val();
      const pob = $('#pob').val();
      const tob = $('#tob').val();

      const profileComplete = dob && pob && tob;

      if (profileComplete) {
        $('.cosmic-profile-section').addClass('completed');
        this.updateProgress();
      } else {
        $('.cosmic-profile-section').removeClass('completed');
      }
    }

    handleQuestionResponse(e) {
      const $question = $(e.target).closest('.cosmic-question');
      const questionKey = $question.data('question');
      const value = e.target.value;

      // Store response
      this.userResponses[questionKey] = value;

      // Mark question as answered
      $question.addClass('answered');

      // Update cosmic preview
      this.updateCosmicPreview();
    }

    handleCheckboxChange(e) {
      const checkbox = e.target;
      const $question = $(checkbox).closest('.cosmic-question');
      const questionKey = $question.data('question');

      // Check if question is answered before allowing confirmation
      const hasAnswer = $question.find('input[type="radio"]:checked').length > 0;

      if (checkbox.checked && !hasAnswer) {
        this.showCosmicMessage('Please select an answer before confirming', 'warning');
        checkbox.checked = false;
        return;
      }

      // Update question appearance
      if (checkbox.checked) {
        $question.addClass('confirmed cosmic-confirmed');
        this.activateQuestionStar(questionKey);
      } else {
        $question.removeClass('confirmed cosmic-confirmed');
        this.deactivateQuestionStar(questionKey);
      }

      this.updateProgress();
      this.checkFormValidity();
    }

    activateQuestionStar(questionKey) {
      const questionIndex =
        Array.from(this.questionBlocks).findIndex(
          block => $(block).data('question') === questionKey
        ) + 3; // Account for cosmic profile questions

      if (questionIndex >= 0 && questionIndex < this.progressStars.length) {
        $(this.progressStars[questionIndex]).addClass('active cosmic-star-active');
      }
    }

    deactivateQuestionStar(questionKey) {
      const questionIndex =
        Array.from(this.questionBlocks).findIndex(
          block => $(block).data('question') === questionKey
        ) + 3;

      if (questionIndex >= 0 && questionIndex < this.progressStars.length) {
        $(this.progressStars[questionIndex]).removeClass('active cosmic-star-active');
      }
    }

    updateProgress() {
      // Count cosmic profile completion
      const profileComplete = $('#dob').val() && $('#pob').val() && $('#tob').val() ? 3 : 0;

      // Count confirmed questions
      const confirmedQuestions = this.confirmCheckboxes.filter(':checked').length;

      this.completedQuestions = profileComplete + confirmedQuestions;
      const progressPercent = (this.completedQuestions / this.totalQuestions) * 100;

      // Update progress bar
      this.progressFill.css('width', `${progressPercent}%`);

      // Update counter
      this.currentQuestionCounter.text(this.completedQuestions);

      // Update progress stars
      for (let i = 0; i < this.completedQuestions; i++) {
        $(this.progressStars[i]).addClass('completed');
      }

      // Add cosmic effects at milestones
      if (progressPercent >= 25 && !this.form.hasClass('quarter-complete')) {
        this.form.addClass('quarter-complete');
        this.showCosmicMessage('✨ Cosmic energies are aligning...', 'info');
      }
      if (progressPercent >= 50 && !this.form.hasClass('half-complete')) {
        this.form.addClass('half-complete');
        this.showCosmicMessage('🌟 Your cosmic profile is emerging...', 'info');
      }
      if (progressPercent >= 75 && !this.form.hasClass('three-quarter-complete')) {
        this.form.addClass('three-quarter-complete');
        this.showCosmicMessage('🚀 Your business destiny awaits...', 'info');
      }
    }

    updateCosmicPreview() {
      const responseCount = Object.keys(this.userResponses).length;

      if (responseCount >= 3 && this.zodiacSign) {
        this.generateCosmicPreview();
        this.cosmicPreview.show().addClass('cosmic-reveal');
      }
    }

    generateCosmicPreview() {
      if (!this.zodiacSign || !vortexQuizData.zodiacSigns[this.zodiacSign]) {
        return;
      }

      const signData = vortexQuizData.zodiacSigns[this.zodiacSign];
      const responses = this.userResponses;

      // Analyze response patterns
      const elementCounts = { fire: 0, earth: 0, air: 0, water: 0 };

      Object.values(responses).forEach(response => {
        switch (response) {
          case 'a':
            elementCounts.fire++;
            break;
          case 'b':
            elementCounts.earth++;
            break;
          case 'c':
            elementCounts.air++;
            break;
          case 'd':
            elementCounts.water++;
            break;
        }
      });

      const dominantElement = Object.keys(elementCounts).reduce((a, b) =>
        elementCounts[a] > elementCounts[b] ? a : b
      );

      const alignment = this.calculateAlignment(signData.element.toLowerCase(), dominantElement);

      this.cosmicPreview.find('.zodiac-traits').html(`
                <div class="preview-zodiac">
                    <h5>${this.getZodiacEmoji(this.zodiacSign)} ${signData.name} Energy</h5>
                    <div class="traits">${signData.traits.join(' • ')}</div>
                </div>
            `);

      this.cosmicPreview.find('.business-alignment').html(`
                <div class="preview-alignment">
                    <h5>🎯 Business Alignment: ${alignment.percentage}%</h5>
                    <p>${alignment.description}</p>
                    <div class="element-bars">
                        ${Object.entries(elementCounts)
                          .map(
                            ([element, count]) => `
                            <div class="element-bar">
                                <span class="element-name">${this.capitalizeFirst(element)}</span>
                                <div class="bar-container">
                                    <div class="bar-fill ${element}" style="width: ${(count / Math.max(...Object.values(elementCounts))) * 100}%"></div>
                                </div>
                                <span class="element-count">${count}</span>
                            </div>
                        `
                          )
                          .join('')}
                    </div>
                </div>
            `);
    }

    calculateAlignment(zodiacElement, dominantElement) {
      const alignments = {
        fire: { fire: 95, air: 80, earth: 60, water: 45 },
        earth: { earth: 95, water: 80, fire: 60, air: 45 },
        air: { air: 95, fire: 80, water: 60, earth: 45 },
        water: { water: 95, earth: 80, air: 60, fire: 45 },
      };

      const percentage = alignments[zodiacElement]?.[dominantElement] || 70;

      const descriptions = {
        95: 'Perfect cosmic alignment! Your responses perfectly match your zodiacal business nature.',
        80: 'Strong alignment! Your natural tendencies support your business instincts.',
        60: "Balanced approach! You're developing complementary business skills.",
        45: "Growth opportunity! You're expanding beyond your natural comfort zone.",
      };

      return {
        percentage,
        description: descriptions[percentage] || "You're on a unique cosmic business journey!",
      };
    }

    capitalizeFirst(str) {
      return str.charAt(0).toUpperCase() + str.slice(1);
    }

    checkFormValidity() {
      const profileComplete = $('#dob').val() && $('#pob').val() && $('#tob').val();
      const allConfirmed =
        this.confirmCheckboxes.length === this.confirmCheckboxes.filter(':checked').length;
      const allAnswered =
        this.questionBlocks.length === this.questionBlocks.filter('.answered').length;

      const isValid = profileComplete && allConfirmed && allAnswered;

      this.submitBtn.prop('disabled', !isValid);

      if (isValid) {
        this.submitBtn.removeClass('disabled').addClass('cosmic-ready');
        this.showCosmicMessage('🌟 Your cosmic business journey is ready to launch!', 'success');
      } else {
        this.submitBtn.removeClass('cosmic-ready').addClass('disabled');
      }
    }

    handleFormSubmit(e) {
      e.preventDefault();

      if (!this.validateForm()) {
        this.showCosmicMessage(
          'Please complete all sections before launching your cosmic journey',
          'error'
        );
        return;
      }

      this.setLoadingState(true);
      const formData = this.collectFormData();
      this.submitCosmicQuiz(formData);
    }

    validateForm() {
      // Validate cosmic profile
      const dob = $('#dob').val();
      const pob = $('#pob').val();
      const tob = $('#tob').val();

      if (!dob || !pob || !tob) {
        this.scrollToSection('.cosmic-profile-section');
        return false;
      }

      // Validate all questions answered and confirmed
      let isValid = true;
      let firstInvalid = null;

      this.questionBlocks.each((index, block) => {
        const $block = $(block);
        const hasAnswer = $block.find('input[type="radio"]:checked').length > 0;
        const isConfirmed = $block.find('.question-confirm:checked').length > 0;

        if (!hasAnswer || !isConfirmed) {
          if (!firstInvalid) {
            firstInvalid = $block;
          }
          isValid = false;
        }
      });

      if (firstInvalid) {
        this.scrollToSection(firstInvalid);
      }

      return isValid;
    }

    scrollToSection(element) {
      $('html, body').animate(
        {
          scrollTop: $(element).offset().top - 100,
        },
        800,
        'easeInOutCubic'
      );
    }

    collectFormData() {
      const data = {
        user_id: this.form.find('input[name="user_id"]').val(),
        cosmic_profile: {
          date_of_birth: $('#dob').val(),
          place_of_birth: $('#pob').val(),
          time_of_birth: $('#tob').val(),
          zodiac_sign: this.zodiacSign,
        },
        questions: this.userResponses,
        cosmic_analysis: {
          element_distribution: this.calculateElementDistribution(),
          business_alignment: this.cosmicPreview.is(':visible')
            ? this.cosmicPreview.find('.preview-alignment h5').text()
            : null,
        },
      };

      return data;
    }

    calculateElementDistribution() {
      const elementCounts = { fire: 0, earth: 0, air: 0, water: 0 };

      Object.values(this.userResponses).forEach(response => {
        switch (response) {
          case 'a':
            elementCounts.fire++;
            break;
          case 'b':
            elementCounts.earth++;
            break;
          case 'c':
            elementCounts.air++;
            break;
          case 'd':
            elementCounts.water++;
            break;
        }
      });

      return elementCounts;
    }

    submitCosmicQuiz(formData) {
      $.ajax({
        url: vortexQuizData.submitEndpoint,
        type: 'POST',
        data: JSON.stringify(formData),
        contentType: 'application/json',
        beforeSend: xhr => {
          xhr.setRequestHeader('X-WP-Nonce', vortexQuizData.nonce);
        },
        success: response => {
          this.handleSubmitSuccess(response);
        },
        error: (xhr, status, error) => {
          this.handleSubmitError(xhr, status, error);
        },
        complete: () => {
          this.setLoadingState(false);
        },
      });
    }

    handleSubmitSuccess(response) {
      if (response.success) {
        this.showCosmicMessage(`🌟 ${response.message}`, 'success');

        // Cosmic celebration animation
        this.triggerCosmicCelebration();

        // Disable form
        this.form.find('input, button').prop('disabled', true);

        // Redirect after celebration
        setTimeout(() => {
          if (response.redirect_url) {
            window.location.href = response.redirect_url;
          }
        }, 3000);
      } else {
        this.showCosmicMessage(
          `⚡ ${response.message || 'An error occurred while processing your cosmic journey.'}`,
          'error'
        );
      }
    }

    handleSubmitError(xhr, status, error) {
      let errorMessage = 'An error occurred while launching your cosmic journey. Please try again.';

      if (xhr.responseJSON && xhr.responseJSON.message) {
        errorMessage = xhr.responseJSON.message;
      }

      this.showCosmicMessage(`⚡ ${errorMessage}`, 'error');
    }

    setLoadingState(loading) {
      if (loading) {
        this.submitBtn.find('.btn-text').hide();
        this.submitBtn.find('.btn-loader').show();
        this.submitBtn.addClass('cosmic-loading');
      } else {
        this.submitBtn.find('.btn-text').show();
        this.submitBtn.find('.btn-loader').hide();
        this.submitBtn.removeClass('cosmic-loading');
      }
    }

    showCosmicMessage(message, type) {
      const messageClass =
        type === 'success'
          ? '.cosmic-success'
          : type === 'error'
            ? '.cosmic-error'
            : '.cosmic-info';

      $(`${messageClass} .message-text`).text(message);
      $(messageClass).show().addClass('cosmic-message-show');

      // Hide other messages
      $('.cosmic-messages > div').not(messageClass).hide().removeClass('cosmic-message-show');

      // Auto-hide info messages
      if (type === 'info') {
        setTimeout(() => {
          $(messageClass).fadeOut().removeClass('cosmic-message-show');
        }, 3000);
      }
    }

    triggerCosmicCelebration() {
      // Create cosmic celebration effects
      const celebration = $('<div class="cosmic-celebration"></div>');

      for (let i = 0; i < 20; i++) {
        const star = $('<div class="celebration-star">✦</div>');
        star.css({
          left: `${Math.random() * 100}%`,
          animationDelay: `${Math.random() * 2}s`,
          animationDuration: `${2 + Math.random() * 3}s`,
        });
        celebration.append(star);
      }

      $('body').append(celebration);

      setTimeout(() => {
        celebration.remove();
      }, 5000);
    }

    initCosmicEffects() {
      // Add floating cosmic particles
      this.createCosmicParticles();

      // Initialize smooth scrolling
      this.initSmoothScrolling();
    }

    createCosmicParticles() {
      const particleContainer = $('<div class="cosmic-particles"></div>');

      for (let i = 0; i < 10; i++) {
        const particle = $('<div class="cosmic-particle">✦</div>');
        particle.css({
          left: `${Math.random() * 100}%`,
          animationDelay: `${Math.random() * 10}s`,
          animationDuration: `${15 + Math.random() * 10}s`,
        });
        particleContainer.append(particle);
      }

      $('.vortex-cosmic-quiz-container').append(particleContainer);
    }

    initSmoothScrolling() {
      // Add easing function for smooth scrolling
      $.easing.easeInOutCubic = function (x, t, b, c, d) {
        if ((t /= d / 2) < 1) {
          return (c / 2) * t * t * t + b;
        }
        return (c / 2) * ((t -= 2) * t * t + 2) + b;
      };
    }
  }

  // Initialize when document is ready
  $(document).ready(() => {
    if ($('#vortex-business-quiz-form').length) {
      new VortexCosmicQuiz();
    }
  });
})(jQuery);
