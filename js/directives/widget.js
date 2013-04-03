//widget directive declaration
angular.module('link-scrapper', [])
.directive('widget', function(){
    return {
      restrict: 'E',
      replace: true,
      transclude: true,
      scope:"isolate",
      templateUrl: 'widget.html',
      // The linking function will add behavior to the template
      link: function(scope, element, attrs) {
        // Title element
        scope.title = attrs.title;
        scope.visible = attrs.visible;
      }
    }
});
