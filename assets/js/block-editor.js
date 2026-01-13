/**
 * FluentCRM-like Block Editor for Email Templates
 */
var GeeCRMBlockEditor = {
    blocks: [],
    blockCounter: 0,

    init: function() {
        this.loadExistingContent();
        this.bindEvents();
    },

    loadExistingContent: function() {
        var $output = jQuery('#gee-crm-html-output');
        var existingHtml = $output.val();
        
        if (existingHtml && existingHtml.trim()) {
            // Parse existing HTML and convert to blocks
            this.parseHtmlToBlocks(existingHtml);
        }
    },

    parseHtmlToBlocks: function(html) {
        // Simple parser - convert HTML to blocks
        var $temp = jQuery('<div>').html(html);
        var self = this;
        
        $temp.children().each(function() {
            var $el = jQuery(this);
            var tag = $el.prop('tagName').toLowerCase();
            
            if (tag === 'h1' || tag === 'h2' || tag === 'h3' || tag === 'h4' || tag === 'h5' || tag === 'h6') {
                self.addBlock('heading', { text: $el.text(), level: tag });
            } else if (tag === 'p') {
                self.addBlock('text', { text: $el.html() });
            } else if (tag === 'hr') {
                self.addBlock('divider', {});
            } else if ($el.hasClass('button') || tag === 'a' && $el.hasClass('button')) {
                self.addBlock('button', { text: $el.text(), url: $el.attr('href') || '#' });
            } else {
                self.addBlock('html', { html: $el[0].outerHTML });
            }
        });
        
        if (this.blocks.length === 0 && html.trim()) {
            // Fallback: add as text block
            this.addBlock('text', { text: html });
        }
    },

    bindEvents: function() {
        var self = this;
        
        // Add block from sidebar
        jQuery('.gee-crm-block-item').on('click', function() {
            var blockType = jQuery(this).data('block-type');
            self.addBlock(blockType);
        });
        
        // Insert variable
        jQuery('.gee-crm-var-btn').on('click', function() {
            var variable = jQuery(this).data('var');
            self.insertVariable(variable);
        });
        
        // Delete block
        jQuery(document).on('click', '.gee-crm-block-delete', function() {
            var blockId = jQuery(this).closest('.gee-crm-block').data('block-id');
            self.removeBlock(blockId);
        });
        
        // Move block up
        jQuery(document).on('click', '.gee-crm-block-move-up', function() {
            var blockId = jQuery(this).closest('.gee-crm-block').data('block-id');
            self.moveBlock(blockId, 'up');
        });
        
        // Move block down
        jQuery(document).on('click', '.gee-crm-block-move-down', function() {
            var blockId = jQuery(this).closest('.gee-crm-block').data('block-id');
            self.moveBlock(blockId, 'down');
        });
        
        // Update block content
        jQuery(document).on('input change', '.gee-crm-block-content input, .gee-crm-block-content textarea, .gee-crm-block-content select', function() {
            self.updateBlockContent();
        });
        
        // Form submit - generate HTML
        jQuery('#gee-template-form').on('submit', function() {
            self.generateHtml();
        });
    },

    addBlock: function(type, data) {
        data = data || {};
        var blockId = 'block-' + (++this.blockCounter);
        var block = {
            id: blockId,
            type: type,
            data: data
        };
        
        this.blocks.push(block);
        this.renderBlock(block);
        this.hideEmptyState();
        this.updateBlockContent();
    },

    renderBlock: function(block) {
        var $container = jQuery('#gee-crm-blocks-container');
        var $block = jQuery('<div class="gee-crm-block" data-block-id="' + block.id + '"></div>');
        
        var blockHtml = this.getBlockHtml(block);
        $block.html(blockHtml);
        
        $container.append($block);
    },

    getBlockHtml: function(block) {
        var html = '<div class="gee-crm-block-header">';
        html += '<span class="gee-crm-block-type">' + this.getBlockTypeLabel(block.type) + '</span>';
        html += '<div class="gee-crm-block-actions">';
        html += '<button type="button" class="gee-crm-block-move-up" title="Move Up"><span class="dashicons dashicons-arrow-up-alt"></span></button>';
        html += '<button type="button" class="gee-crm-block-move-down" title="Move Down"><span class="dashicons dashicons-arrow-down-alt"></span></button>';
        html += '<button type="button" class="gee-crm-block-delete" title="Delete"><span class="dashicons dashicons-trash"></span></button>';
        html += '</div></div>';
        html += '<div class="gee-crm-block-content">';
        html += this.getBlockContentHtml(block);
        html += '</div>';
        
        return html;
    },

    getBlockContentHtml: function(block) {
        var html = '';
        
        switch(block.type) {
            case 'text':
                html = '<textarea class="gee-crm-block-text" placeholder="Enter text content...">' + (block.data.text || '') + '</textarea>';
                break;
            case 'heading':
                var level = block.data.level || 'h2';
                html = '<select class="gee-crm-block-heading-level">';
                for (var i = 1; i <= 6; i++) {
                    html += '<option value="h' + i + '"' + (level === 'h' + i ? ' selected' : '') + '>H' + i + '</option>';
                }
                html += '</select>';
                html += '<input type="text" class="gee-crm-block-heading-text" value="' + (block.data.text || '') + '" placeholder="Enter heading text...">';
                break;
            case 'button':
                html = '<input type="text" class="gee-crm-block-button-text" value="' + (block.data.text || 'Click Here') + '" placeholder="Button text">';
                html += '<input type="url" class="gee-crm-block-button-url" value="' + (block.data.url || '#') + '" placeholder="Button URL">';
                break;
            case 'divider':
                html = '<div class="gee-crm-divider-preview"><hr></div>';
                break;
            case 'spacer':
                var height = block.data.height || '20';
                html = '<input type="number" class="gee-crm-block-spacer-height" value="' + height + '" min="10" max="100" placeholder="Height (px)">';
                break;
            case 'html':
                html = '<textarea class="gee-crm-block-html" placeholder="Enter HTML code...">' + (block.data.html || '') + '</textarea>';
                break;
        }
        
        return html;
    },

    getBlockTypeLabel: function(type) {
        var labels = {
            'text': 'Text',
            'heading': 'Heading',
            'button': 'Button',
            'divider': 'Divider',
            'spacer': 'Spacer',
            'html': 'HTML'
        };
        return labels[type] || type;
    },

    removeBlock: function(blockId) {
        if (confirm('Are you sure you want to delete this block?')) {
            this.blocks = this.blocks.filter(function(b) { return b.id !== blockId; });
            jQuery('.gee-crm-block[data-block-id="' + blockId + '"]').remove();
            this.updateBlockContent();
            if (this.blocks.length === 0) {
                this.showEmptyState();
            }
        }
    },

    moveBlock: function(blockId, direction) {
        var index = this.blocks.findIndex(function(b) { return b.id === blockId; });
        if (index === -1) return;
        
        var newIndex = direction === 'up' ? index - 1 : index + 1;
        if (newIndex < 0 || newIndex >= this.blocks.length) return;
        
        // Swap blocks
        var temp = this.blocks[index];
        this.blocks[index] = this.blocks[newIndex];
        this.blocks[newIndex] = temp;
        
        // Re-render
        this.reRenderBlocks();
    },

    reRenderBlocks: function() {
        var $container = jQuery('#gee-crm-blocks-container');
        $container.empty();
        
        var self = this;
        this.blocks.forEach(function(block) {
            self.renderBlock(block);
        });
    },

    updateBlockContent: function() {
        var self = this;
        jQuery('.gee-crm-block').each(function() {
            var $block = jQuery(this);
            var blockId = $block.data('block-id');
            var block = self.blocks.find(function(b) { return b.id === blockId; });
            
            if (!block) return;
            
            switch(block.type) {
                case 'text':
                    block.data.text = $block.find('.gee-crm-block-text').val();
                    break;
                case 'heading':
                    block.data.level = $block.find('.gee-crm-block-heading-level').val();
                    block.data.text = $block.find('.gee-crm-block-heading-text').val();
                    break;
                case 'button':
                    block.data.text = $block.find('.gee-crm-block-button-text').val();
                    block.data.url = $block.find('.gee-crm-block-button-url').val();
                    break;
                case 'spacer':
                    block.data.height = $block.find('.gee-crm-block-spacer-height').val() || '20';
                    break;
                case 'html':
                    block.data.html = $block.find('.gee-crm-block-html').val();
                    break;
            }
        });
    },

    insertVariable: function(variable) {
        var $focused = jQuery(':focus');
        if ($focused.length && ($focused.is('textarea') || $focused.is('input[type="text"]'))) {
            var current = $focused.val();
            var cursorPos = $focused[0].selectionStart || current.length;
            var newValue = current.substring(0, cursorPos) + variable + current.substring(cursorPos);
            $focused.val(newValue);
            $focused[0].setSelectionRange(cursorPos + variable.length, cursorPos + variable.length);
            this.updateBlockContent();
        }
    },

    generateHtml: function() {
        this.updateBlockContent();
        
        var html = '<div style="max-width:600px; margin:0 auto; font-family:Arial, sans-serif; line-height:1.6; color:#333;">';
        
        var self = this;
        this.blocks.forEach(function(block) {
            switch(block.type) {
                case 'text':
                    html += '<p style="margin:0 0 15px 0;">' + (block.data.text || '').replace(/\n/g, '<br>') + '</p>';
                    break;
                case 'heading':
                    var level = block.data.level || 'h2';
                    html += '<' + level + ' style="margin:20px 0 15px 0; font-weight:600;">' + (block.data.text || '') + '</' + level + '>';
                    break;
                case 'button':
                    html += '<p style="margin:20px 0; text-align:center;">';
                    html += '<a href="' + (block.data.url || '#') + '" style="display:inline-block; padding:12px 24px; background:#2271b1; color:#fff; text-decoration:none; border-radius:4px; font-weight:600;">' + (block.data.text || 'Click Here') + '</a>';
                    html += '</p>';
                    break;
                case 'divider':
                    html += '<hr style="border:none; border-top:1px solid #ddd; margin:20px 0;">';
                    break;
                case 'spacer':
                    html += '<div style="height:' + (block.data.height || '20') + 'px;"></div>';
                    break;
                case 'html':
                    html += block.data.html || '';
                    break;
            }
        });
        
        html += '</div>';
        
        jQuery('#gee-crm-html-output').val(html);
    },

    hideEmptyState: function() {
        jQuery('#gee-crm-empty-state').hide();
    },

    showEmptyState: function() {
        jQuery('#gee-crm-empty-state').show();
    }
};

// Initialize when DOM is ready
jQuery(document).ready(function() {
    if (jQuery('#gee-crm-blocks-container').length) {
        GeeCRMBlockEditor.init();
    }
});

