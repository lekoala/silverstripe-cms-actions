<% if $UseButtonTag %>
    <%-- when need btn__title to hide content when loading --%>
	<button $getAttributesHTML('class') class="btn<% if $extraClass %> $extraClass<% end_if %>">
		<span class="btn__title"><% if $ButtonContent %>$ButtonContent<% else %>$Title.XML<% end_if %></span>
	</button>
<% else %>
	<input $getAttributesHTML('class') class="btn<% if $extraClass %> $extraClass<% end_if %>"/>
<% end_if %>
