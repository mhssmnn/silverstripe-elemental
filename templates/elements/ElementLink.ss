<% if LinkType == 'External' %>
	<figure class="$Type<% if $ExtraClass %> $ExtraClass<% end_if %>">
		<a class="external_link" href="$LinkURL" <% if NewWindow %>target="_blank"<% end_if %>><% if LinkText %>$LinkText<% else %>$LinkURL<% end_if %></a>
		<% if LinkDescription %><figcaption class="link_description">$LinkDescription</figcaption><% end_if %>
	</figure>
<% else %>
	<figure class="$Type<% if $ExtraClass %> $ExtraClass<% end_if %>">
		<a class="internal_link" href="$InternalLink.Link" <% if NewWindow %>target="_blank"<% end_if %>><% if LinkText %>$LinkText<% else %>$InternalLink.Link<% end_if %></a>
		<% if LinkDescription %><figcaption class="link_description">$LinkDescription</figcaption><% end_if %>
	</figure>
<% end_if %>