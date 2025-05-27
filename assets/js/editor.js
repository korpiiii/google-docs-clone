// Initialize the editor
const editor = new EditorJS({
    holder: 'editor',
    tools: {
        header: {
            class: Header,
            config: {
                placeholder: 'Enter a header',
                levels: [1, 2, 3],
                defaultLevel: 1
            }
        },
        list: {
            class: List,
            inlineToolbar: true
        },
        paragraph: {
            class: Paragraph,
            inlineToolbar: true
        },
        image: {
            class: ImageTool,
            config: {
                endpoints: {
                    byFile: '../ajax/upload_image.php'
                }
            }
        }
    },
    onReady: () => {
        console.log('Editor.js is ready');
    },
    onChange: (api, event) => {
        // Auto-save functionality will be handled in document.js
    }
});
