# News Related Plus

Adds support of `contao-legacy/news_related` to the news list module

## Technical instructions

As in `<contao-legacy/news_related>/templates/modules/mod_newsreader.html5`, add the following snippet to your `news_latest.html5` template:

```
<?php if ($this->newsRelated): ?>
    <div class="news_related block">
        <div class="related_headline"><h2><?php echo $this->related_headline; ?></h2></div>
        <div class="related_content">
            <div class="related_info"><?php echo $this->info; ?></div>

            <div class="related_items block">
                <?php foreach ($this->newsRelated as $related): ?>
                    <div class="related_item block">
                        <?php if ($related['image']): ?>
                            <figure class="image_container">
                                <?php if ($related['url']): ?>
                                <a href="<?php echo $related['url']; ?>" title="<?php echo $related['image']['alt']; ?>" rel="lightbox">
                                    <?php endif; ?>

                                    <?php $this->insert('picture_default', $related['image']['picture']); ?>

                                    <?php if ($related['url']): ?>
                                </a>
                            <?php endif; ?>
                            </figure>
                        <?php endif; ?>

                        <p class="info">
                            <time
                                datetime="<?php echo $related['datetime']; ?>"><?php echo $related['date']; ?></time> <?php echo $related['commentCount']; ?>
                        </p>

                        <div class="item_headline"><h3><a href="<?php echo $related['url']; ?>"
                                                          title="<?php echo $related['title']; ?>"><?php echo $related['headline']; ?></a></h3></div>

                        <div class="item_teaser"><?php echo $related['teaser']; ?> <span class="more"><?php echo $related['more']; ?></span></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endif; ?>
```