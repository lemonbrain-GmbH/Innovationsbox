<script>
  (function() {
    function showInputs(inputName, value) {
      const parent = '%parent%';
      if (parent.length > 0) {
        const regexp = /\[(.*?)\]/g;
        const nameSplit = Array.from(inputName.match(regexp));
        let searchName = '%parent%';
        if (nameSplit.length > 0) {
          //repeatable
          const positionString = nameSplit[0]
          const position = parseInt(positionString.substring(1, positionString.length - 1))
          if (position >= 0) {
            searchName += '[' + position + ']';
          }

          waitForInput('input[name="' + searchName + '[%prefix%link_file]' + '"]').then((e) => {
            window.document.querySelector('input[name="' + searchName + '[%prefix%link_intern]' + '"]').parentElement.parentElement.parentElement.hidden = true
            window.document.querySelector('input[name="' + searchName + '[%prefix%link_file]' + '"]').parentElement.parentElement.parentElement.hidden = true
            window.document.querySelector('input[name="' + searchName + '[%prefix%link_extern]' + '"]').parentElement.parentElement.hidden = true
            window.document.querySelector('input[name="' + searchName + '[%prefix%link_hash_bang]' + '"]').parentElement.parentElement.hidden = true
            window.document.querySelector('input[name="' + searchName + '[%prefix%link_contact]' + '"]').parentElement.hidden = true
            window.document.querySelector('input[name="' + searchName + '[%prefix%link_subject]' + '"]').parentElement.hidden = true
            window.document.querySelector('textarea[name="' + searchName + '[%prefix%link_content]' + '"]').parentElement.hidden = true

            if (value && value !== '') {
              switch (value) {
                case 'intern':
                  window.document.querySelector('input[name="' + searchName + '[%prefix%link_intern]' + '"]').parentElement.parentElement.parentElement.hidden = false
                  window.document.querySelector('input[name="' + searchName + '[%prefix%link_hash_bang]' + '"]').parentElement.parentElement.hidden = false
                  break;
                case 'extern':
                  window.document.querySelector('input[name="' + searchName + '[%prefix%link_extern]' + '"]').parentElement.parentElement.hidden = false
                  break;
                case 'file':
                  window.document.querySelector('input[name="' + searchName + '[%prefix%link_file]' + '"]').parentElement.parentElement.parentElement.hidden = false
                  break;
                case 'contact':
                  window.document.querySelector('input[name="' + searchName + '[%prefix%link_contact]' + '"]').parentElement.hidden = false
                  window.document.querySelector('input[name="' + searchName + '[%prefix%link_subject]' + '"]').parentElement.hidden = false
                  window.document.querySelector('textarea[name="' + searchName + '[%prefix%link_content]' + '"]').parentElement.hidden = false
                  break;
                default:
                  console.log('error: no option for this value: ' + value);
              }
            }
          });
        }
      } else {
        window.document.querySelector('input[name="%prefix%link_intern"]').parentElement.parentElement.parentElement.hidden = true
        window.document.querySelector('input[name="%prefix%link_file"]').parentElement.parentElement.parentElement.hidden = true
        window.document.querySelector('input[name="%prefix%link_extern"]').parentElement.parentElement.hidden = true
        window.document.querySelector('input[name="%prefix%link_hash_bang"]').parentElement.parentElement.hidden = true
        window.document.querySelector('input[name="%prefix%link_contact"]').parentElement.hidden = true
        window.document.querySelector('input[name="%prefix%link_subject"]').parentElement.hidden = true
        window.document.querySelector('textarea[name="%prefix%link_content"]').parentElement.hidden = true

        if (value && value !== '') {
          switch (value) {
            case 'intern':
              window.document.querySelector('input[name="%prefix%link_intern"]').parentElement.parentElement.parentElement.hidden = false
              window.document.querySelector('input[name="%prefix%link_hash_bang"]').parentElement.parentElement.hidden = false
              break;
            case 'extern':
              window.document.querySelector('input[name="%prefix%link_extern"]').parentElement.parentElement.hidden = false
              break;
            case 'file':
              window.document.querySelector('input[name="%prefix%link_file"]').parentElement.parentElement.parentElement.hidden = false
              break;
            case 'contact':
              window.document.querySelector('input[name="%prefix%link_contact"]').parentElement.hidden = false
              window.document.querySelector('input[name="%prefix%link_subject"]').parentElement.hidden = false
              window.document.querySelector('textarea[name="%prefix%link_content"]').parentElement.hidden = false
              break;
            default:
              console.log('error: no option for this value: ' + value);
          }
        }
      }
    }

    function init(onNewEntry = false, entryCount = 0) {
      const prefix = '%prefix%';
      const parent = '%parent%';
      
      function changeHandler(select) {
        const linkTypeName = select.name
        const linkTypeValue = select.value
        showInputs(linkTypeName, linkTypeValue);
      }

      if (parent.length > 0) {
        const selects = window.document.querySelectorAll('select[name*="%prefix%link_type"]');

        if (onNewEntry) {
          waitForInput(`.sortable-item[data-id="${entryCount + 1}"]`).then((e) => {
              init();
          })
        }

        window.selectsLength = selects.length
        for (const select of selects) {
          changeHandler(select)

          function change(e) {
            changeHandler.call(this, e.target)
          }
          select.removeEventListener('change', change)
          select.addEventListener('change', change)
        }
      } else {
          const select = window.document.querySelector('select[name="%prefix%link_type"]');
          changeHandler(select)
          function change(e) {
            changeHandler.call(this, e.target)
          }
          select.removeEventListener('change', change)
          select.addEventListener('change', change)
      }
    }

    function waitForInput(selector) {
      return new Promise(resolve => {
        if (document.querySelector(selector)) {
          return resolve(document.querySelector(selector));
        }
        const observer = new MutationObserver(mutations => {
          if (document.querySelector(selector)) {
            resolve(document.querySelector(selector));
            observer.disconnect();
          }
        });
        observer.observe(document.body, {
          childList: true,
          subtree: true
        });
      });
    }

    // for links not in a repeatable, we use "link_file"
    // with the new file selector from concrete, this will be the slowest one to initialize
    let formInitTrigger = 'input[name="%prefix%link_file"]';

    const isRepeated = '%parent%';
    if (isRepeated.length > 0) {
      waitForInput('select[name="' + '%parent%[1]' + '[%prefix%link_type]' + '"]').then((e) => {

        // we need to wait for the last (repCount) "link_file" input to be loaded before running init()
        let repCount = e.closest('.sortable-items').querySelectorAll('.sortable-item').length
        formInitTrigger = 'input[name="' + '%parent%['+ repCount +']' + '[%prefix%link_intern]' + '"]';
        waitForInput(formInitTrigger).then((e) => {
          Array.from(document.querySelectorAll('#ccm-block-form .add-entry')).forEach(
            e => e.addEventListener("click", () => {
              init(true, window.document.querySelectorAll('select[name*="%prefix%link_type"]').length)
            })
          );
          init();
        });
      });
    } else {
      waitForInput(formInitTrigger).then((e) => {
        Array.from(document.querySelectorAll('#ccm-block-form .add-entry')).forEach(
          e => e.addEventListener("click", () => {
            init(true, window.document.querySelectorAll('select[name*="%prefix%link_type"]').length)
          })
        )
        init();
      });
    }
  })();
</script>
