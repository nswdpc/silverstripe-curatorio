<%-- feed is displayed based on an IncludeSocialFeed variable you can add and the existence of a feed --%>
<% if $IncludeSocialFeed && $SiteConfig.CuratorFeedRecord %>
    <% with $SiteConfig %>
        <% if $SocialFeedTitle %>
            <h2>{$SocialFeedTitle}</h2>
        <% end_if %>
        {$CuratorFeedRecord}
    <% end_with %>
<% end_if %>
