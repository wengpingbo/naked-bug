var nakedBug = {
	curpage : 0,
	maxpage : 0,
	//type: 0 -- previous, 1 -- next
	getPostLists : function(type) {
		if(type == 0)
		{
		  if(nakedBug.curpage > 0) nakedBug.curpage -= 1;
		  else {
			  //disable previous button
			  $("#previous").parent().attr("class", "disabled");
			  $("#previous").click();
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
			  $("#next").click();
			  return;
		  }
		}
		$.get(
			  "utils.php",
			  //if the len is not 20, you should pass $len value in below
			  //param.
			  {op : "alist", start : nakedBug.curpage*14},
			  function(data) {
				  if(data.length == 0 || data == "\n") nakedBug.maxpage = nakedBug.curpage;
				  else $("#post_toc").html(data);
			  }
		);
		if(type == 0 && $("#next").parent().attr("class") == "disabled")
		{
		  //active next button
			 $("#next").parent().attr("class", "");
			 $("#next").click(function() {nakedBug.getPostLists(1);}); 
		}
		if(type == 1 && $("#previous").parent().attr("class") == "disabled")
		{
		  //active previous button
			 $("#previous").parent().attr("class", "");
			 $("#previous").click(function() {nakedBug.getPostLists(0);}); 
		}
	},

	indexInitialize : function() {
		 $("#previous").click( 
		   function() {nakedBug.getPostLists(0);});
		 $("#next").click(
		   function() {nakedBug.getPostLists(1);});
		 //disable previous page button
		 $("#previous").parent().attr("class", "disabled");
		 // load first 20 posts
		 $.get(
			   "utils.php",
			   {op : "alist", start : 0},
			   function(data) {
				  if(data.length != 0 && data != "\n") $("#post_toc").html(data);
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
	  }
};

nakedBug.Initialize();
