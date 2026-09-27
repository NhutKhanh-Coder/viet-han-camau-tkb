
                    function autoFitVideo(v) {
                        if (!v || !v.videoWidth || !v.videoHeight) return;
                        var ratio = v.videoWidth / v.videoHeight;
                        if (ratio >= 1.25) {
                            v.style.objectFit = 'cover';
                            v.style.objectPosition = 'center center';
                        } else {
                            v.style.objectFit = 'contain';
                            v.style.objectPosition = 'center center';
                        }
                    }
                    