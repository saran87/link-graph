

	'use strict';

	//Controller declaration
	function LinkScrapperCtrl ($scope){

		$scope.title = "Link Scrapper";
		$scope.query = "www.google.com";
		$scope.content = "";
		$scope.links = [];
	
		$scope.getPage = function(){

			$scope.$broadcast('ChangeRoot');							
		}

		$('#input').animate({'padding-top':0},1000);

	};


