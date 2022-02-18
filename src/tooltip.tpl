$('<div class="tooltip-inner" id="{id}-tooltip"></div>').css({
          position: 'absolute',
          display : 'none',
          opacity : 0.8
        }).appendTo('body')
        $('#{id}').bind('plothover', function (event, pos, item) {
          if (item) {
            var x = item.datapoint[0].toFixed(2),
                y = item.datapoint[1].toFixed(2)
            {calculations}
            $('#{id}-tooltip').html({tooltext})
              .css({
                top : item.pageY + 5,
                left: item.pageX + 5
              })
              .fadeIn(200)
          } else {
            $('#{id}-tooltip').hide()
          }
        });