(function ($, Drupal, BrowserSolc, log) {
  "use strict";

  // Disable Form Submit until we have compiled
  // the Solidity source code from edit-contract-src into
  // the field contract_compiled_json.
  document.getElementById('edit-submit').disabled = true;

  // Initialize solc
  var timer = setInterval(function() {
    if (window.BrowserSolc !== 'undefined'){
      // Init after BrowserSolc is loaded.
      solcInit({}, solcCompilerReady);
      clearInterval(timer);
    }
  }, 200);


  /**
   * updateAbiField()
   *
   * @param compiled string
   *   Json, Solc compiler output.
   */
  function updateAbiField(compiled) {

    if (compiled) {
      var abi = JSON.parse(compiled.interface);

      $('input[name="contract_compiled_json"]').val(JSON.stringify(compiled));

      document.getElementById('abi').innerHTML = Drupal.theme.prototype.abiTable(abi);
      document.getElementById('edit-submit').disabled = false;
    }
  }

  Drupal.theme.prototype.abiTable = function (abi) {
    var html = '<table>';

    html += '<tr>' +
        '<th>function</th>' +
        '<th>return type</th>' +
        '<th>type</th>' +
        '<th>constant</th>' +
        '<th>payable</th>' +
        '<th>stateMutability</th>' +
        '</tr>';
    abi.forEach(function (elm) {
      html += '<tr>';
      html += Drupal.theme.prototype.abiTableRow(elm);
      html += '</tr>'
    });
    html += '</table>';
    return html;
  };

  /**
   *
   * We are compiling the contract source using Javascript based solc compiler.
   * @see https://github.com/ericxtang/browser-solc.
   *
   * @param options
   *    Compile options. See settings below.
   * @param cb
   *    Callback. This callback can use the solc compiler, when ready.
   *    See solcCompilerReady(solc).
   */
  function solcInit(options, cb) {

    var settings = $.extend({
      // These are the defaults.
      version: '0.4.24',
      optimize: 1
    }, options);

    $(document).once('initSolc').each(function () {
      $.solc = {
        settings: settings,
        updateVersions: function (soljsonSources, soljsonReleases) {
          $.solc.soljsonSources = soljsonSources;
          $.solc.soljsonReleases = soljsonReleases;
          $.solc.setCompiler();
        },
        setCompiler: function () {
          // @todo We should get solc version from the contract!!
          window.BrowserSolc.loadVersion($.solc.soljsonReleases[this.settings.version], $.solc.compileReady);
        },
        compileReady: function (solc) {
          $.solc.compiler = solc;
          cb($.solc);
        },
        contractNameFromSrc: function (src) {
          // @see https://regex101.com/r/c0qwPf/1
          var reg = new RegExp(/contract[\s]*([a-zA-Z].*)[\s]+{/g);
          var res = reg.exec(src);
          //log(res[1], 'contractName @ contractName');
          return res ? res[1] : null;
        },
        compileFromSrc: function (source, cb) {
          var contractId = this.contractNameFromSrc(source);
          if (!this.compiler) {
            log('No compiler available.');
          }
          if (contractId) {
            var compiled = this.compiler.compile(source, this.optimize)
            log('ERROR: solc compile failed. Check your contract code with Solc compiler version '
                + this.settings.version
                + '. You might change compiler version or test and debug at http://remix.ethereum.org'
                , compiled.errors
            );
            cb(compiled.contracts[':' + contractId]);
          }
          else {
            log('Could not determine contract class name from sourcecode.');
          }
        }
      };
      window.BrowserSolc.getVersions($.solc.updateVersions);
    });
  }


  /**
   * Compiling solidity code
   *
   * Initially and onChange.
   *
   * @param solc object
   *
   */
  function solcCompilerReady(solc) {
    var contractSrc = $('#edit-contract-src');

    // Set up a change listener.
    contractSrc.bind('input propertychange', function() {
      if(this.value.length){
        log('Contract source changed');
        $.solc.compileFromSrc(this.value, updateAbiField);
      }
    });
    if(contractSrc.length) {
      $.solc.compileFromSrc(contractSrc.val(), updateAbiField);
    }
  }



  Drupal.theme.prototype.abiTableRow = function (elm) {
    // log(elm, 'elm');

    var params = '';
    if (elm.inputs) {
      elm.inputs.forEach(function (p, i) {
        params += p.type + ' ' + p.name;
        if (i < elm.inputs.length - 1) {
          params += ', ';
        }
      });
    }

    var ret = '';
    if (elm.outputs) {
      elm.outputs.forEach(function (r) {
        ret += r.type;
      });
    }

    var html = '';
    html += '<td>' + elm.name + '(' + params + ')' + '</td>';
    html += '<td>' + ret + '</td>';
    html += '<td>' + elm.type + '</td>';
    html += '<td>' + elm.constant + '</td>';
    html += '<td>' + elm.payable + '</td>';
    html += '<td>' + elm.stateMutability + '</td>';
    return html;
  };

})(window.jQuery, window.Drupal, window.BrowserSolc, window.console.log);
