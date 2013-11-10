var nakedBug = {
	curpage : 0,
	maxpage : 0,
	curcatalog : "",
	//type: 0 -- previous, 1 -- next, 2 -- first page
	getPostLists : function(type) {
		if(type == 0)
		{
		  if(nakedBug.curpage > 0) nakedBug.curpage -= 1;
		  else {
			  //disable previous button
			  $("#previous").parent().attr("class", "disabled");
			  return;
		  }
		}
		if(type == 1 )
		{ 
		  if(nakedBug.maxpage == 0 || nakedBug.curpage < nakedBug.maxpage) 
			nakedBug.curpage += 1;
		  else {
			  //disable next button
			  $("#next").parent().attr("class", "disabled"); 
			  return;
		  }
		}
		if(type == 2 )
		{ 
		  nakedBug.curpage = 0;
		  nakedBug.maxpage = 0;
		  //disable next button
		  $("#previous").parent().attr("class", "disabled");
		}
		$.get(
			  "utils.php",
			  //if the len is not 14, you should pass $len value in below
			  //param.
			  {op : "alist", start : nakedBug.curpage*14, catalog : nakedBug.curcatalog},
			  function(data) {
				  if(data.length == 0 || data == "\n") nakedBug.maxpage = nakedBug.curpage;
				  else $("#post_toc").html(data);
			  }
		);
		if(type == 0 && $("#next").parent().attr("class") == "disabled")
		{
		  //active next button
			 $("#next").parent().attr("class", "");
		}
		if(type == 1 && $("#previous").parent().attr("class") == "disabled")
		{
		  //active previous button
			 $("#previous").parent().attr("class", "");
		}
	},

	indexInitialize : function() {
		 $("#previous").click( 
		   function() {nakedBug.getPostLists(0);});
		 $("#next").click(
		   function() {nakedBug.getPostLists(1);});
		 //disable previous page button
		 $("#previous").parent().attr("class", "disabled");
		 // load first page
		 nakedBug.getPostLists(2);
		 // load catalog to nav
		 $.get(
			   "utils.php",
			   {op : "gcatalog"},
			   function(data) {
				  if(data.length != 0 && data != "\n") $("#nav_item").append(data);
			   }
		 );
		 $("#nav_hook").on("click", ".dropdown_item", 
			  function() { 
				  nakedBug.curcatalog = $(this).text();
				  nakedBug.getPostLists(2);
			  });
	 },

	articleIntialize : function() {
		//load social comment plugin
		$.get(
			  "utils.php",
			  {op : "comment"},
			  function(data) {
				  $("#article_comment").html(data);
			  }
		);
	},

	Initialize : function() {
		 //load top nav
		 $.get(
				"utils.php",
				{op : "gnav"},
				function(data) {
				  $("#nav_hook").append(data);
				}
		  );
		  //load footer
		  $.get(
				"utils.php",
				{op : "footer"},
				function(data) {
				  $("#footer_hook").append(data);
				}
		  );
		//check current page type, index or article
		var pagetype = $("body").data("pagetype");
		if(pagetype == "index") nakedBug.indexInitialize();
		else nakedBug.articleInitialize();
	  }
};

$(nakedBug.Initialize());
